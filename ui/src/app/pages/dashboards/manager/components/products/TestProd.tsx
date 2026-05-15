import { usePurchasesQuery } from '@/app/store/features/business/purchases/purchasesQuery';
import React, { useEffect } from 'react';

export const TestProd: React.FC = () => {
  const { data } = usePurchasesQuery();
  const sales = data?.purchases ?? data ?? [];
  let bsProducts = new Map();
  let pdt: any = {};
  useEffect(() => {
    if (sales) {
      sales.forEach((sale: any) => {
        sale.purchase_items.forEach((item: any) => {
          const product = item.product;
          if (!bsProducts.has(product.id)) {
            bsProducts.set(product.id, {
              id: product.id,
              cid: product.category_id,
              name: product.name,
              sku: product.sku,
              costPrice: item.cost_price,
              price: product.price,
              quantity: item.quantity,
              status: product.status,
            });
          }
        });
        console.log('BsProduct here==>', bsProducts);
      });
      //   sales.map((sale) =>
      //     sale.purchase_items.map((item) => {
      //       pdt.cid = item.product.category_id;
      //       pdt.name = item.product.name;
      //       pdt.sku = item.product.sku;
      //       pdt.costPrice = item.cost_price;
      //       pdt.price = item.product.price;
      //       pdt.quantity = item.quantity;
      //       pdt.status = item.product.status;

      //       // push object to an array
      //       bsProducts.push(pdt);
      //     }),
      //   );
    }
  }, [sales]);
  //   console.log('products available==>', sales);
  return <div>{sales && sales.map((sale) => sale.purchase_items.map((item) => <p>{item.product.name}</p>))}</div>;
};
