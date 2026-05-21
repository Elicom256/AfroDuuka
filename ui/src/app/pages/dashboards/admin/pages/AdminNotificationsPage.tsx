import React from "react";

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

import {
  Bell,
  CheckCheck,
  AlertTriangle,
  Package,
  Users,
  ShoppingCart,
  Settings,
  Trash2,
} from "lucide-react";

export const AdminNotificationsPage = () => {
  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">
            Notifications
          </h1>

          <p className="text-muted-foreground">
            Monitor alerts, updates, and system activities.
          </p>
        </div>

        <div className="flex gap-2">
          <Button variant="outline">
            <CheckCheck className="mr-2 h-4 w-4" />
            Mark all as read
          </Button>

          <Button variant="destructive">
            <Trash2 className="mr-2 h-4 w-4" />
            Clear All
          </Button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardContent className="flex items-center justify-between p-6">
            <div>
              <p className="text-sm text-muted-foreground">
                Total Notifications
              </p>
              <h2 className="text-3xl font-bold">248</h2>
            </div>

            <Bell className="h-10 w-10 text-muted-foreground" />
          </CardContent>
        </Card>

        <Card>
          <CardContent className="flex items-center justify-between p-6">
            <div>
              <p className="text-sm text-muted-foreground">Unread</p>
              <h2 className="text-3xl font-bold">17</h2>
            </div>

            <AlertTriangle className="h-10 w-10 text-yellow-500" />
          </CardContent>
        </Card>

        <Card>
          <CardContent className="flex items-center justify-between p-6">
            <div>
              <p className="text-sm text-muted-foreground">
                Orders Alerts
              </p>
              <h2 className="text-3xl font-bold">63</h2>
            </div>

            <ShoppingCart className="h-10 w-10 text-muted-foreground" />
          </CardContent>
        </Card>

        <Card>
          <CardContent className="flex items-center justify-between p-6">
            <div>
              <p className="text-sm text-muted-foreground">
                Stock Warnings
              </p>
              <h2 className="text-3xl font-bold">11</h2>
            </div>

            <Package className="h-10 w-10 text-muted-foreground" />
          </CardContent>
        </Card>
      </div>

      {/* Notifications List */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Notifications</CardTitle>

          <CardDescription>
            Latest updates from your business system
          </CardDescription>
        </CardHeader>

        <CardContent className="space-y-4">
          {[
            {
              title: "Low stock alert",
              description:
                "Cooking Oil stock is below minimum threshold.",
              icon: Package,
              badge: "Inventory",
              time: "2 mins ago",
            },
            {
              title: "New employee attendance",
              description:
                "John Doe checked in for morning shift.",
              icon: Users,
              badge: "Attendance",
              time: "10 mins ago",
            },
            {
              title: "Promotion activated",
              description:
                "Weekend discount campaign is now active.",
              icon: Bell,
              badge: "Promotions",
              time: "30 mins ago",
            },
            {
              title: "System settings updated",
              description:
                "Reports module configuration changed.",
              icon: Settings,
              badge: "System",
              time: "1 hour ago",
            },
            {
              title: "New order received",
              description:
                "A new POS transaction was completed.",
              icon: ShoppingCart,
              badge: "Sales",
              time: "2 hours ago",
            },
          ].map((notification, index) => {
            const Icon = notification.icon;

            return (
              <div
                key={index}
                className="flex items-start justify-between rounded-lg border p-4 transition hover:bg-muted/40"
              >
                <div className="flex gap-4">
                  <div className="flex h-11 w-11 items-center justify-center rounded-full bg-muted">
                    <Icon className="h-5 w-5" />
                  </div>

                  <div className="space-y-1">
                    <div className="flex items-center gap-2">
                      <h3 className="font-medium">
                        {notification.title}
                      </h3>

                      <Badge variant="secondary">
                        {notification.badge}
                      </Badge>
                    </div>

                    <p className="text-sm text-muted-foreground">
                      {notification.description}
                    </p>

                    <span className="text-xs text-muted-foreground">
                      {notification.time}
                    </span>
                  </div>
                </div>

                <Button size="sm" variant="ghost">
                  View
                </Button>
              </div>
            );
          })}
        </CardContent>
      </Card>
    </div>
  );
};