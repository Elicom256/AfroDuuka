# Containerization

## Docker Image build errors (front end image)

During docker build, I'm encounterinmg this error

=> [build 5/6] COPY --link . .                                                                                                                  7.7s 
 => => merging                                                                                                                                   1.0s 
 => ERROR [build 6/6] RUN npm run build                                                                                                         36.3s
------
 > [build 6/6] RUN npm run build:
2.525 
2.525 > ui@0.0.0 build
2.525 > tsc -b && vite build
2.525 
27.38 src/app/pages/dashboards/admin/components/analytics/SalesAnalytics.tsx(17,29): error TS6133: 'formatPeriodLabel' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/attendance/Attendance.tsx(6,9): error TS6133: 'data' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/attendance/AttendancePanel.tsx(18,9): error TS6133: 'formatted' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/branches/AddBranch.tsx(21,34): error TS6133: 'error' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/notifications/NotificationItem.tsx(5,43): error TS6133: 'MailCheck' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/payment-gateways/AddPaymentGateway.tsx(4,1): error TS6133: 'Input' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/products/AddProductCategory.tsx(22,1): error TS6192: All imports in import declaration are unused.
27.38 src/app/pages/dashboards/admin/components/products/BusinessProducts.tsx(8,1): error TS6133: 'AddProductCategory' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/products/ProductCategories.tsx(17,9): error TS6133: 'navigate' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/products/ProductTable.tsx(2,1): error TS6133: 'useProductsQuery' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/products/ProductTable.tsx(4,1): error TS6192: All imports in import declaration are unused.
27.38 src/app/pages/dashboards/admin/components/products/ProductTable.tsx(7,10): error TS6133: 'Link' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/products/ProductTable.tsx(8,1): error TS6133: 'toast' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/products/ProductTable.tsx(9,1): error TS6133: 'Spinner' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/products/ProductTable.tsx(12,3): error TS6133: 'useDeleteBranchProductMutation' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/purchases/Purchase.tsx(9,1): error TS6133: 'useNavigate' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/purchases/Purchase.tsx(32,9): error TS6133: 'getProductName' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/reports/BranchPerformanceReport.tsx(34,9): error TS6133: 'activeBranch' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/salary/EmployeeSalaryForm.tsx(46,47): error TS6133: 'error' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/sales/Sale.tsx(9,1): error TS6133: 'useNavigate' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/sales/Sale.tsx(32,9): error TS6133: 'getProductName' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/workers/AddAttendanceForm.tsx(31,13): error TS6133: 'payload' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/workers/AttendanceHistory.tsx(16,67): error TS6133: 'attendanceHistory' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/components/workers/WorkerBasicInfo.tsx(3,1): error TS6133: 'Badge' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminAnalyticsPage.tsx(4,1): error TS6133: 'CustomersAnalytics' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminAnalyticsPage.tsx(5,1): error TS6133: 'SuppliersAnalytics' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminCouponsPage.tsx(1,1): error TS6133: 'React' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminDashboardPage.tsx(1,1): error TS6133: 'useMemo' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminEmployeeSalaryPage.tsx(9,1): error TS6133: 'EmployeeSalaryForm' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminEmployeeSalaryPage.tsx(14,10): error TS6133: 'editItem' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminEmployeeSalaryPage.tsx(15,10): error TS6133: 'formOpen' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminEmployeeSalaryPage.tsx(40,9): error TS6133: 'handleFormClose' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminHistoryPage.tsx(1,1): error TS6133: 'React' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminInventoryPage.tsx(1,1): error TS6133: 'React' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminPromotionsPage.tsx(1,1): error TS6133: 'React' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminPurchasesPage.tsx(8,1): error TS6133: 'useProductsQuery' is declared but its value is never read.
27.38 src/app/pages/dashboards/admin/pages/AdminTaxesPage.tsx(54,44): error TS2322: Type 'string | number' is not assignable to type 'string'.
27.38   Type 'number' is not assignable to type 'string'.
27.39 src/app/pages/dashboards/admin/pages/AdminWorkersPage.tsx(3,3): error TS6133: 'useRegisterWorkerMutation' is declared but its value is never read.
27.39 src/app/pages/dashboards/admin/pages/AdminWorkersPage.tsx(4,3): error TS6133: 'useUpdateWorkerMutation' is declared but its value is never read.
27.39 src/app/pages/dashboards/admin/pages/admin-placeholder-pages.tsx(126,9): error TS6133: 'suppliers' is declared but its value is never read.
27.39 src/app/pages/dashboards/admin/pages/settings.tsx(16,3): error TS6133: 'LayoutDashboard' is declared but its value is never read.
27.39 src/app/pages/dashboards/manager/components/products/AddProduct.tsx(17,10): error TS6133: 'useProductCategoriesQuery' is declared but its value is never read.
27.39 src/app/pages/dashboards/manager/components/products/EditProduct.tsx(15,10): error TS6133: 'useProductCategoriesQuery' is declared but its value is never read.
27.39 src/app/pages/dashboards/manager/components/products/TestProd.tsx(8,7): error TS6133: 'pdt' is declared but its value is never read.
27.39 src/app/pages/dashboards/manager/components/purchases/Purchase.tsx(9,1): error TS6133: 'useNavigate' is declared but its value is never read.
27.39 src/app/pages/dashboards/manager/components/purchases/Purchase.tsx(32,9): error TS6133: 'getProductName' is declared but its value is never read.
27.39 src/app/pages/dashboards/manager/pages/ManagerProductsPage.tsx(1,29): error TS6133: 'CardDescription' is declared but its value is never read.
27.39 src/app/pages/dashboards/manager/pages/ManagerPurchasesPage.tsx(8,1): error TS6133: 'useProductsQuery' is declared but its value is never read.
27.39 src/app/pages/dashboards/manager/pages/ManagerWorkersPage.tsx(5,1): error TS6133: 'resolveList' is declared but its value is never read.
27.39 src/app/pages/dashboards/manager/pages/components/attendance/AttendanceRecordItem.tsx(1,1): error TS6133: 'ReactNode' is declared but its value is never read.
27.39 src/app/routes/AdminRoutes.tsx(21,1): error TS6133: 'NotFound' is declared but its value is never read.
27.39 src/app/routes/ManagerRoutes.tsx(19,1): error TS6133: 'NotFound' is declared but its value is never read.
27.39 src/app/routes/StaffDashboard.tsx(8,1): error TS6133: 'NotFound' is declared but its value is never read.
-

Cleanup the front end for a clean docker image build
Work like a senior devops engineer