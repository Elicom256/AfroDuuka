import { db } from './db';
import type { LocalProduct } from './types';

export async function cacheProducts(products: any[]): Promise<void> {
  const now = Date.now();
  const rows: LocalProduct[] = products.map((p) => ({
    id: Number(p.id),
    name: p.name,
    sku: p.sku ?? null,
    barcode: p.barcode ?? null,
    selling_price: Number(p.selling_price ?? 0),
    stock: Number(p.stock ?? 0),
    tax_rate: Number(p.tax_rate ?? 0),
    is_tax_inclusive: !!p.is_tax_inclusive,
    product_category: p.product_category ?? null,
    status: p.status,
    cachedAt: now,
  }));
  await db.products.bulkPut(rows);
}

export async function searchProductsOffline(q: string): Promise<any[]> {
  const term = q.trim().toLowerCase();
  if (!term) return [];
  const all = await db.products.toArray();
  return all
    .filter((p) => {
      const barcode = (p.barcode ?? '').toLowerCase();
      const sku = (p.sku ?? '').toLowerCase();
      const name = (p.name ?? '').toLowerCase();
      return (
        barcode.startsWith(term) ||
        sku.startsWith(term) ||
        name.includes(term)
      );
    })
    .sort((a, b) => {
      const aBarcode = (a.barcode ?? '').toLowerCase();
      const bBarcode = (b.barcode ?? '').toLowerCase();
      const aSku = (a.sku ?? '').toLowerCase();
      const bSku = (b.sku ?? '').toLowerCase();
      const rank = (v: string) => (v.startsWith(term) ? 0 : 1);
      const r = rank(aBarcode) - rank(bBarcode) || rank(aSku) - rank(bSku);
      if (r !== 0) return r;
      return a.name.localeCompare(b.name);
    })
    .slice(0, 20);
}

export async function getProductByBarcodeOffline(code: string): Promise<any | null> {
  const trimmed = code.trim();
  const found = await db.products.where('barcode').equals(trimmed).first();
  return found ?? null;
}

export async function clearProductCache(): Promise<void> {
  await db.products.clear();
}

export async function getProductCacheCount(): Promise<number> {
  return db.products.count();
}
