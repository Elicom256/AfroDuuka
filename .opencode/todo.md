# MODULE 2 — Returns / Refunds

Implement complete sales return and purchase return workflows.

## Backend

Create:

SaleReturn
SaleReturnItem

PurchaseReturn
PurchaseReturnItem

Fields include:

* original transaction
* reason
* notes
* quantity
* refund amount
* restock flag
* condition
* processed_by
* timestamps

When processing returns:

Sales Return

* validate original sale
* prevent returning more than sold
* restore stock if restock=true
* create stock movements
* create cashflow adjustment
* preserve audit trail

Purchase Return

* reduce inventory
* update supplier balances where appropriate
* create stock movements
* create cashflow adjustment

Never delete inventory history.

## Frontend

Create pages for:

Sales Returns

Purchase Returns

Features:

* Search original invoice
* Select returned items
* Partial returns
* Full returns
* Return reason
* Condition
* Refund amount
* Notes
* Confirmation dialog
* Return history
* Printable return receipt
