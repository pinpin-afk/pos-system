<?php

namespace App\Enums;

enum Permission: string
{
    case DashboardView = 'dashboard.view';
    case ProductsView = 'products.view';
    case ProductsManage = 'products.manage';
    case CategoriesManage = 'categories.manage';
    case BrandsManage = 'brands.manage';
    case StockView = 'stock.view';
    case StockAdjust = 'stock.adjust';
    case StockOpname = 'stock.opname';
    case CustomersView = 'customers.view';
    case CustomersManage = 'customers.manage';
    case SuppliersView = 'suppliers.view';
    case SuppliersManage = 'suppliers.manage';
    case PurchasesView = 'purchases.view';
    case PurchasesManage = 'purchases.manage';
    case SalesView = 'sales.view';
    case SalesRefund = 'sales.refund';
    case SalesVoid = 'sales.void';
    case ReportsView = 'reports.view';
    case UsersManage = 'users.manage';
    case SettingsManage = 'settings.manage';
    case ImportsManage = 'imports.manage';
    case ExportsView = 'exports.view';
    case LabelsPrint = 'labels.print';
    case ShiftsView = 'shifts.view';
    case ProfileManage = 'profile.manage';
    case BranchesManage = 'branches.manage';
    case WarehousesManage = 'warehouses.manage';
    case TransfersView = 'transfers.view';
    case TransfersManage = 'transfers.manage';
    case ExpensesView = 'expenses.view';
    case ExpensesManage = 'expenses.manage';
    case PromotionsManage = 'promotions.manage';
    case ActivityView = 'activity.view';
    case InsightsView = 'insights.view';
}
