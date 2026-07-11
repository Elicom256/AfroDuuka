# Refactor on business level

Refactor the products hierarchy to
product_category -> product
Meaning now, you'll replace:

- products with product_categories
- business_branch_products with products
- Move the sku, barcode and track_serial to new product model
  So after this, the business branches of a business will all have products, not business_branch_products


delete category

After this, clean up the front end to to match the new implementation

## Constraints

- Do not hallucinate
- DO the clean up for both front end and back end
- Use proper naming that match with a specific module
- Rename every related thing eg models, migrations, etc
- Edit migrations directly since I'm still in production'
