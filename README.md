# Finscope – Personal Finance & Financial Insights Platform

## 1. Project Overview

### Project Name

**Finscope**

### Overview

Finscope is a web-based personal finance management platform designed to help users track, understand, and improve their financial activity from a single dashboard.

The platform allows users to record and review income and expenses, organize transactions into categories, analyze spending patterns through visual dashboards, and obtain financial insights based on their activity. Finscope also provides additional analysis and simulation features to help users understand their financial position and make better financial decisions.

The goal of Finscope is to turn raw financial transactions into simple, understandable information that users can act upon.

--- 

## 2. Problem It Solves

Many users know how much money they earn and spend, but do not have a clear understanding of where their money goes or how their spending behavior affects their financial health.

Traditional methods such as manually maintaining spreadsheets can be time-consuming and difficult to maintain. Users may also struggle to identify spending patterns, compare categories, understand their financial progress, or estimate the effect of future financial decisions.

Finscope addresses these problems by bringing transaction tracking, categorization, visual analysis, financial insights, and simulation into one platform.

The platform aims to help users:

* Track income and expenses in an organized manner.
* Understand spending patterns through category-wise analysis.
* View financial information through dashboards and charts.
* Review transaction history in one place.
* Analyze their overall financial position.
* Explore possible financial outcomes using simulation features.
* Make better financial decisions using personalized insights.

---

## 3. Target Users (Personas)

### Persona 1 – Student / Young Adult

A student or young adult who wants to understand their spending habits and manage limited monthly income.

**Needs:**

* Simple expense tracking.
* Category-wise spending information.
* Monthly income and expense overview.
* Easy-to-understand financial insights.

### Persona 2 – Salaried Professional

A working professional who wants to monitor monthly income, expenses, savings, and financial trends.

**Needs:**

* Detailed transaction history.
* Income and expense analysis.
* Category breakdowns.
* Financial health indicators.
* Better visibility into monthly spending behavior.

### Persona 3 – Financial Planner / Goal-Oriented User

A user who wants to understand future financial outcomes and evaluate different spending or saving scenarios.

**Needs:**

* Financial simulations.
* Trend analysis.
* Spending insights.
* Historical comparisons.
* Data-driven decision support.

---

## 4. Vision Statement

**To make personal financial management simple, visual, and actionable by helping users understand their money, identify spending patterns, and make better financial decisions through intelligent financial insights.**

---

## 5. Key Features / Goals

### Core Features

1. **User Account Management**

   * User sign-in and account management.
   * Profile and account settings.

2. **Income & Expense Tracking**

   * Record financial transactions.
   * Track income and expenses.
   * Maintain transaction history.

3. **Category Management**

   * Organize financial activity into categories.
   * Analyze spending category by category.

4. **Financial Dashboard**

   * Display total balance.
   * Show income and expense summaries.
   * Present important financial indicators.
   * Provide visual representations of financial activity.

5. **Expense & Spending Analysis**

   * View category-wise expense distribution.
   * Identify major spending areas.
   * Analyze financial behavior.

6. **Financial Insights**

   * Provide meaningful observations based on financial activity.
   * Help users understand their financial behavior.

7. **Financial Score / Health Analysis**

   * Provide an overall indication of the user's financial position.
   * Help users identify areas that may need improvement.

8. **Financial Simulator**

   * Allow users to explore possible financial outcomes.
   * Support decision-making through scenario-based analysis.

9. **Data Import**

   * Support importing financial data from supported files such as CSV.
   * Support processing of uploaded financial statement information.

10. **Historical Analysis**

* Review previous transactions and financial activity.
* Compare financial patterns over time.

### Project Goals

* Make personal finance easier to understand.
* Reduce the effort required to manually analyze spending.
* Provide a single platform for financial tracking and analysis.
* Convert financial data into useful visual and actionable insights.
* Encourage better financial planning and spending awareness.

---

## 6. Success Metrics

The success of Finscope can be measured using the following indicators:

* Number of registered users.
* Number of financial transactions recorded.
* Percentage of users who return to the dashboard regularly.
* Number of users actively analyzing their expenses.
* Usage of category-wise financial analysis.
* Usage of financial simulation features.
* Number of imported financial files.
* User engagement with financial insights.
* Reduction in the time required to understand monthly financial activity.
* User satisfaction with the clarity and usefulness of financial information.

---

## 7. Assumptions & Constraints

### Assumptions

* Users are willing to manually enter or import their financial transaction data.
* Users understand basic financial concepts such as income, expenses, balance, and categories.
* Users have access to a web browser and an internet-connected device when using the hosted application.
* Financial data provided by the user is sufficiently accurate for analysis.
* Users benefit from visual dashboards and summarized financial insights.

### Constraints

* Financial analysis depends on the accuracy and completeness of user-provided data.
* The initial version focuses on personal finance management rather than direct banking integration.
* Advanced financial advisory or professional financial planning is outside the scope of the current version.
* The quality of automated insights depends on the available transaction data.
* External banking integrations and real-time bank synchronization may require additional services and infrastructure.
* The project is developed within an academic project timeline and therefore focuses on practical core functionality rather than a full commercial financial platform.

---

## 8. Project Scope

### In Scope

* Personal financial tracking.
* Income and expense management.
* Transaction history.
* Category-based analysis.
* Financial dashboard.
* Charts and financial visualizations.
* Financial insights.
* Financial score / health analysis.
* Financial simulation.
* Supported financial data import.
* User profile and account management.

### Out of Scope

* Direct banking transactions.
* Money transfers.
* Investment execution.
* Loan processing.
* Professional financial advisory services.
* Real-time bank account synchronization unless separately integrated.

## Quick Start – Local Development

### Prerequisites
- Docker Desktop
- Git
- Web browser

### Run the application with Docker

1. Clone the repository and enter the project folder.
2. Build the Docker images:

```bash
docker compose build


## Quick Start – Local Development

### Run with Docker

```bash
docker compose build
docker compose up -d
docker compose ps

