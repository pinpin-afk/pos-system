<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Support\LocationContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvService
{
    public function __construct(private LocationContext $location) {}

    /**
     * @param  list<string>  $headers
     * @param  iterable<int, array<int|string, mixed>>  $rows
     */
    public function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return list<array<int, string>>
     */
    public function read(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'File CSV tidak bisa dibaca.',
            ]);
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        }

        fclose($handle);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'File CSV kosong.',
            ]);
        }

        return $rows;
    }

    public function importProducts(UploadedFile $file, User $user, StockService $stockService): int
    {
        $rows = $this->read($file);
        $header = array_map(fn (string $value) => strtolower($value), array_shift($rows));
        $imported = 0;

        DB::transaction(function () use ($rows, $header, $user, $stockService, &$imported): void {
            foreach ($rows as $index => $row) {
                $data = $this->mapRow($header, $row);
                $name = $data['name'] ?? '';
                $sku = $data['sku'] ?? '';
                $categoryName = $data['category'] ?? '';
                $barcode = $data['barcode'] ?? '';
                $wholesale = $data['wholesale_price'] ?? '';
                $unit = $data['unit'] ?? '';

                if ($name === '' || $sku === '' || $categoryName === '') {
                    throw ValidationException::withMessages([
                        'file' => 'Baris '.($index + 2).' wajib punya name, sku, dan category.',
                    ]);
                }

                $category = Category::query()->firstOrCreate(
                    ['name' => $categoryName],
                    ['is_active' => true],
                );

                $product = Product::query()->updateOrCreate(
                    ['sku' => $sku],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'barcode' => $barcode !== '' ? $barcode : null,
                        'purchase_price' => (float) ($data['purchase_price'] ?? 0),
                        'selling_price' => (float) ($data['selling_price'] ?? 0),
                        'wholesale_price' => $wholesale !== '' ? (float) $wholesale : null,
                        'unit' => $unit !== '' ? $unit : 'PCS',
                        'is_active' => true,
                    ],
                );

                $product->load('stock');

                if ($product->stock === null) {
                    $product->stock()->create([
                        'warehouse_id' => $this->location->warehouse($user)?->id,
                        'quantity' => 0,
                        'minimum_stock' => (float) ($data['minimum_stock'] ?? 0),
                    ]);
                    $product->load('stock');
                }

                $qty = (float) ($data['stock'] ?? 0);

                if ($qty > 0 && (float) $product->stock()->value('quantity') === 0.0) {
                    $stockService->recordInitial($product->fresh('stock'), $qty, $user);
                }

                $imported++;
            }
        });

        return $imported;
    }

    public function importCustomers(UploadedFile $file): int
    {
        $rows = $this->read($file);
        $header = array_map(fn (string $value) => strtolower($value), array_shift($rows));
        $imported = 0;

        DB::transaction(function () use ($rows, $header, &$imported): void {
            foreach ($rows as $index => $row) {
                $data = $this->mapRow($header, $row);
                $name = $data['name'] ?? '';

                if ($name === '') {
                    throw ValidationException::withMessages([
                        'file' => 'Baris '.($index + 2).' wajib punya name.',
                    ]);
                }

                $member = $data['member_number'] ?? '';
                $phone = $data['phone'] ?? '';
                $email = $data['email'] ?? '';
                $address = $data['address'] ?? '';
                $birthday = $data['birthday'] ?? '';

                Customer::query()->updateOrCreate(
                    ['member_number' => $member !== '' ? $member : 'IMP-'.($index + 2)],
                    [
                        'branch_id' => $this->location->branch()?->id,
                        'name' => $name,
                        'phone' => $phone !== '' ? $phone : null,
                        'email' => $email !== '' ? $email : null,
                        'address' => $address !== '' ? $address : null,
                        'birthday' => $birthday !== '' ? $birthday : null,
                        'is_walk_in' => false,
                    ],
                );

                $imported++;
            }
        });

        return $imported;
    }

    public function importStock(UploadedFile $file): int
    {
        $rows = $this->read($file);
        $header = array_map(fn (string $value) => strtolower($value), array_shift($rows));
        $imported = 0;

        DB::transaction(function () use ($rows, $header, &$imported): void {
            foreach ($rows as $index => $row) {
                $data = $this->mapRow($header, $row);
                $sku = $data['sku'] ?? '';

                if ($sku === '') {
                    throw ValidationException::withMessages([
                        'file' => 'Baris '.($index + 2).' wajib punya sku.',
                    ]);
                }

                $product = Product::query()->where('sku', $sku)->first();

                if ($product === null) {
                    throw ValidationException::withMessages([
                        'file' => 'SKU '.$sku.' tidak ditemukan.',
                    ]);
                }

                Stock::query()->where('product_id', $product->id)->update([
                    'quantity' => (float) ($data['quantity'] ?: 0),
                    'minimum_stock' => (float) ($data['minimum_stock'] ?: $product->stock?->minimum_stock ?: 0),
                ]);

                $imported++;
            }
        });

        return $imported;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $row
     * @return array<string, string>
     */
    private function mapRow(array $header, array $row): array
    {
        $mapped = [];

        foreach ($header as $index => $key) {
            $mapped[$key] = $row[$index] ?? '';
        }

        return $mapped;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty();
    }
}
