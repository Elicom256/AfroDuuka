# Task: Build the AI Inventory Assistant

You are a senior full-stack engineer working on an existing production-grade Laravel + React (TypeScript) inventory management system.

Your goal is to implement the AI Inventory Assistant while respecting the existing architecture and coding patterns.

---

## First Objective: Understand the Codebase

**Do not start coding immediately.**

First, thoroughly inspect the project to understand its architecture.

Specifically:

* Traverse the backend and frontend codebase.
* Understand the folder structure.
* Identify existing conventions.
* Understand how services, models, requests, controllers, repositories (if any), and utilities are organized.
* Understand authentication and authorization flow.
* Identify existing API response patterns.
* Identify existing frontend component patterns.
* Understand how pages are composed.
* Understand how API calls are currently made.
* Understand state management already in use.
* Understand styling conventions.

Also inspect the recently added AI architecture:

```
app/AI/
    Agent.php
    Tool.php
    ToolRegistry.php
    Tools/
```

Understand the intended responsibility of each class before implementing anything.

Do not introduce architecture that conflicts with the existing project.

---

# AI Architecture

The AI system should follow this design.

```
User
    ↓
Agent
    ↓
ToolRegistry
    ↓
Specific Tool
    ↓
Models
    ↓
Database
```

The Agent orchestrates.

The ToolRegistry discovers and resolves tools.

Each Tool contains one business capability.

Do not place business logic inside the Agent.

Do not place business logic inside the ToolRegistry.

Business logic belongs inside the Tool classes.

---

# Tool Design

Every tool must extend the abstract Tool class.

Each tool should expose:

* name()
* description()
* parameters()
* handle()

Do not invent different APIs for different tools.

Every tool must behave consistently.

---

# Backend Requirements

Implement the AI tools that make sense from the current database schema.

Examples include:

* Product Search
* Latest Sold Product
* Sales Summary
* Revenue Report
* Cash Collected
* Profit Summary
* Slow Moving Products
* Fast Moving Products
* Low Stock Products
* Out of Stock Products
* Top Selling Products
* Worst Selling Products
* Product History
* Stock Valuation
* Inventory Movement
* Sales vs Purchases
* Pending Payments
* Customer Purchases
* Supplier Purchases
* Business Insights
* Daily Business Summary
* Compare Periods

If the database does not support a tool, clearly explain why instead of hallucinating an implementation.

Never fabricate database fields.

Never fabricate relationships.

---

# Agent

Implement the Agent so it can:

* receive a user prompt
* determine whether a tool should be called
* invoke the correct tool through the ToolRegistry
* return the tool result

The Agent should not know implementation details of tools.

---

# Tool Registry

Implement a registry capable of:

* registering tools
* listing all tools
* finding a tool by name
* resolving tool instances

Avoid hardcoding logic inside the Agent.

---

# Frontend

Do not redesign the application.

Do not reinvent the UI.

Study the current frontend first.

Follow the existing design system.

Follow the existing layout.

Follow the existing spacing.

Follow the existing typography.

Follow the existing component organization.

Use existing patterns wherever possible.

---

## Chat UI

Create the AI chat interface as its own reusable component.

Then render that component inside:

```
src/app/pages/dashboards/admin/pages/AdminDashboardPage.tsx
```

Do not build the entire chat inside the page.

The page should simply compose the component.

---

## Components

Prefer existing project components.

When new components are needed:

* use shadcn/ui components
* use Lucide React icons
* maintain the existing visual language
* keep the UI modern and minimal

Avoid custom CSS unless absolutely necessary.

---

# Code Quality

Write production-quality code.

Prefer readability over cleverness.

Use dependency injection where appropriate.

Keep classes focused on a single responsibility.

Avoid duplicated logic.

Avoid dead code.

Avoid premature abstractions.

---

# Error Handling

Handle failures gracefully.

Return meaningful error messages.

Never swallow exceptions silently.

---

# Performance

Avoid N+1 queries.

Use eager loading when appropriate.

Avoid unnecessary database queries.

Keep responses efficient.

---

# Security

Validate all incoming data.

Respect authentication.

Respect authorization.

Never expose sensitive data.

Never trust user input.

---

# Constraints

* Do not hallucinate models.
* Do not hallucinate database columns.
* Do not hallucinate relationships.
* Do not invent APIs that do not exist.
* Do not introduce unnecessary dependencies.
* Do not rewrite unrelated parts of the application.
* Do not change existing architecture unless absolutely necessary.
* Do not break existing functionality.

If something is missing, inspect the codebase first before making assumptions.

---

# Expected Output

Produce clean, maintainable, production-ready code that integrates naturally with the existing Laravel + React application while following the newly introduced AI architecture.
