// components/inventory/InventorySummary.tsx

import type { LucideIcon } from 'lucide-react';

type StatItem = {
  title: string;
  value: string | number;
  icon?: LucideIcon;
  valueClassName?: string;
  className?: string;
};

type InventorySummaryProps = {
  stats: StatItem[];
};

export const InventorySummary = ({ stats }: InventorySummaryProps) => {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-4 gap-4">
      {stats.map((stat, index) => {
        const Icon = stat.icon;

        return (
          <div
            key={index}
            className={`rounded-2xl bg-muted p-2 transition-all hover:shadow-sm ${stat.className || ''}`}
          >
            <div className="flex items-center justify-between">
              <p className="text-sm text-muted-foreground">{stat.title}</p>
              {Icon && <Icon className="h-5 w-5 text-muted-foreground" />}
            </div>

            <p
              className={`mt-2 sm font-semibold italic tracking-tight ${stat.valueClassName || ''}`}
            >
              {stat.value}
            </p>
          </div>
        );
      })}
    </div>
  );
};