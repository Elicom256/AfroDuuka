# Bugs to correct

## Product update

_When a purchase is made, a product's cost and selling prices should be updated pointing to the sale made, also the quantity should be updated. Currently, when the purchase is made, none of those gets updated on the product._
_On the products table in the UI(executive's dashboard), the delete button should not appear there, it should be on the single product's page (dashboard/products/id)_
**Possible solution:**

- When a purchase is to be made, the user should be able to update the selling price of that purchased product if there is a need. In case the price has changed, the user is able to update the selling price without first going to _update product_
  After this error is corrected and the product gets fully updated after a purchase correctly, this saves the time of the user, and the platform feels more smooth to use
- Remove the delete button under Actions column of products on the executive dashboard

## Receipt design

Currently, the receipt does not show the business name, instead, it shows only the branch name.
Also, the receipt lacks a qrcode to the platform as a minor way of marketing
**Solution:**
The branch name should come just after the business name on the receipt
There should be a qrcode to the platform's main page. It should be well located on the receipt
In fact, the receipt needs more re-design with a business logo somewhere

## Export Failure

_Currently, all exports are failing, wherever data is being exported, the action can't succeed, eg on sales, purchases, products_

Here, you'll need to check the reason for the failure and look for the best possible solution
After this task, data is expected to be exported in xlsx files

## Expenses

Mos of the expenses are not included in the analytics, as if those on analytics are filtered to consider only expenses on purhcases. All expenses should be considered
Also, approve expense is not going through, check the reason and correct it. In fact, it's updating, but the toast shows it failed, yet it went through

## Analytics

Some data is failing to load on the analytics page, actually, they're six cards and the sixth is the one failing to load
So, check the issue and correct that error

## Transactions

The transactions page is not accessible, it should be here=> /dashboard/finance/transactions

## Payrol and salaries separation

Currently, they're all doing the same role, so they confuse the user
**Possible solution**
change EmployeeSalary model to Salary, here, you'll re-name the model, controller, form requests, migrations, seeders etc, so generally rename everything related with EmployeeSalary to Salary. Also the routes, the change should affect both the front end and backend
After this change, the role of Salary model is to connect each role to a salary. Meaning the employee's salary will be pre-determined by his role
**Forexample:** Role: Operations, Salary: 20000. So, the Salary should have a foreign key of the role
The Salary will have these columns
id, bueiness_id, business_branch_id, role_id, amount, period(or any name which fits well to represent that the salary is monthly/yearly), status(enum of active, inactive), set_by(who set the salary)
Now, the business_branch_id should be nullable, in case all the branches have similar salaries by role

## Branches

Branches should be fully manageable by the executive, and also, there should be easy view/management of a single branch
Currently, the executive reaches dashboard/branches only, and he should access a branch by id like dashboard/branches/id when he taps on a branch
Then on that page(dashboard/branches/id), there should be full summarised info about the branch eg
Number if workers, products currently sold there, finances (expenses vs income), etc, all those should be cards
Also, there should be an edit and delete button on this page

## Reports

The reports are failing to be loaded on /dashboard/finance/reports, so, the user can't see any reports of his business
check the issue and correct it

## Subscriptions (Leave this page for now)

On this page(/dashboard/subscriptions), the user shouldn't be able to input the amount to pay, because the platform already has fixed amount to pay, this ensures that books are balanced properly during accountability
It also ensures that the system automatically knows which tier the user is paying for
**Note:** Leave this page for now

## Currecy Rates

This page should pull currecy rates from google or any trusted currecy rate api, it should be asy to use. It should have an option to select currecies side by side for easy comparison
**Forexample:** UGX | USD
4000 | 1
In a tabular format

## Workers

First things first, remove business_id and business_branch_id from worker, because they already appear on user, who's a worker
clean up that from model -> migration etc

The executive is failing to add workers to any branch, yet he should have the ability to add workers to any branch
currently, when he tries adding a worker, he gets this error on any branch that's not main,
**The server rejected that request. Check the values and try again.**
And when he tries to add a worker to the main branch
**SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique constraint "workers_employee_code_unique" DETAIL: Key (employee_code)=(EMP-00001) already exists. (Connection: pgsql, Host: pgsql, Port: 5432, Database: inventory, SQL: insert into "workers" ("user_id", "employee_code", "department", "position", "employment_type", "salary", "hire_date", "status", "business_id", "business_branch_id", "updated_at", "created_at") values (23, EMP-00001, ?, ?, full_time, ?, ?, active, 1, 1, 2026-10-08 19:44:29, 2026-10-08 19:44:29) returning "id")
**

Workers are under **People** section on the side menu, Here, there are no suppliers and customers currently. You'll add those pages there, good enough they're already handled at the backend. So you'll add those 2 routes on the front end, and enable the executive and branch manager to manage these people

## Stock transfer

Here, the user gets an error when trying to dispatch transfered products from one branch to another
**SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique constraint "products_business_branch_id_name_unique" DETAIL: Key (business_branch_id, name)=(2, iPhone 15 Pro Max) already exists. (Connection: pgsql, Host: pgsql, Port: 5432, Database: inventory, SQL: insert into "products" ("business_branch_id", "product_category_id", "tax_category_id", "name", "sku", "barcode", "quantity", "cost_price", "selling_price", "is_tax_inclusive", "reorder_level", "description", "status", "updated_at", "created_at") values (2, 1, ?, iPhone 15 Pro Max, PH-1001-1, 890100000001, 0, 4888321.00, 2444160.50, false, 9, some description for the product here, active, 2026-10-08 20:24:02, 2026-10-08 20:24:02) returning "id")
**

So, normalize this for a successful transfer

## Product Audit

here (dashboard/product-audits), the user cannot select products on his branch/business, so this aborts product auditing

## Financial audit

Here (dashboard/financial-audits/1), the performed by is empty, this should be the person who recorded it, and he's already known, but not well retrieved
Then, there should be a way to add approved by.

## Activity logs

Currently, all logs are being retrieved for the executive, he should be able to view them anyway, but by filtering, but he should see only important ones from the first look. Not x logged in,

So make sure he sees only important ones unless he filters
Also, non-executive employees should see their own logs
