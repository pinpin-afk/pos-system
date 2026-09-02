<?php

namespace App\Support;

use App\Enums\Permission;
use App\Enums\UserRole;

class RolePermissions
{
    /**
     * @return list<Permission>
     */
    public static function for(UserRole $role): array
    {
        return match ($role) {
            UserRole::Owner => Permission::cases(),
            UserRole::Administrator => [
                Permission::DashboardView,
                Permission::ProductsView,
                Permission::ProductsManage,
                Permission::CategoriesManage,
                Permission::BrandsManage,
                Permission::StockView,
                Permission::StockAdjust,
                Permission::StockOpname,
                Permission::CustomersView,
                Permission::CustomersManage,
                Permission::SuppliersView,
                Permission::SuppliersManage,
                Permission::PurchasesView,
                Permission::PurchasesManage,
                Permission::SalesView,
                Permission::SalesRefund,
                Permission::ReportsView,
                Permission::UsersManage,
                Permission::ImportsManage,
                Permission::ExportsView,
                Permission::LabelsPrint,
                Permission::ShiftsView,
                Permission::ProfileManage,
                Permission::BranchesManage,
                Permission::WarehousesManage,
                Permission::TransfersView,
                Permission::TransfersManage,
                Permission::ExpensesView,
                Permission::ExpensesManage,
                Permission::PromotionsManage,
                Permission::ActivityView,
                Permission::InsightsView,
            ],
            UserRole::Manager => [
                Permission::DashboardView,
                Permission::ProductsView,
                Permission::StockView,
                Permission::StockAdjust,
                Permission::StockOpname,
                Permission::CustomersView,
                Permission::SuppliersView,
                Permission::PurchasesView,
                Permission::PurchasesManage,
                Permission::SalesView,
                Permission::SalesRefund,
                Permission::SalesVoid,
                Permission::ReportsView,
                Permission::ExportsView,
                Permission::LabelsPrint,
                Permission::ShiftsView,
                Permission::ProfileManage,
                Permission::TransfersView,
                Permission::TransfersManage,
                Permission::ExpensesView,
                Permission::ExpensesManage,
                Permission::PromotionsManage,
                Permission::InsightsView,
            ],
            UserRole::Supervisor => [
                Permission::DashboardView,
                Permission::ProductsView,
                Permission::StockView,
                Permission::CustomersView,
                Permission::SuppliersView,
                Permission::PurchasesView,
                Permission::SalesView,
                Permission::ReportsView,
                Permission::ExportsView,
                Permission::LabelsPrint,
                Permission::ShiftsView,
                Permission::ProfileManage,
                Permission::TransfersView,
                Permission::ExpensesView,
                Permission::InsightsView,
            ],
            UserRole::Cashier => [
                Permission::ProfileManage,
            ],
        };
    }
}
