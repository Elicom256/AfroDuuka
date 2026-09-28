export const resolveList = (data: any, key?: string) => {
  if (!data) return [] as any[];
  if (Array.isArray(data)) return data;
  if (Array.isArray(data.data)) return data.data;
  if (key && Array.isArray(data[key])) return data[key];
  return [] as any[];
};
