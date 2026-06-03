# Chapter 4 System Analysis and Design

Chapter 4 presents the system analysis and design of the web-based house rental management system. This chapter translates the research method and development approach described in Chapter 3 into a concrete system structure. It explains the system scope, functional and non-functional requirements, architecture, process modeling, database design, and design rationale that guide the implementation of the application.

## 4.1 System Overview

The proposed system is a web-based house rental management platform designed to support the rental workflow for three main user roles: tenant, agent, and admin. The system centralizes property information, rental request processing, transaction tracking, contract handling, communication, and monitoring functions into one integrated application. By using a browser/server architecture, the platform allows users to access the system through a web browser without requiring a standalone desktop installation.

From a functional perspective, the system is intended to reduce manual coordination in rental operations and improve the consistency of transaction processing. Tenants can browse properties, submit rental requests, communicate with agents, and view rental-related records. Agents can manage property information, review requests, approve or reject transactions, and handle contract-related activities. Admins can monitor system activities, review transaction data, and supervise platform usage at the system level.

The system also maintains transaction traceability through controlled status transitions, role-based access control, and structured storage of user, property, request, contract, message, and audit data. These characteristics make the application suitable for a rental environment where data consistency, accountability, and workflow clarity are important.

## 4.2 Functional Requirements

Functional requirements describe the services and behaviors that the system must provide to support the rental process. In this study, the requirements are derived from the lecturer-provided assignment, the refined dissertation project assignment, the project report baseline, and the current system scope. The requirements are organized by role and system-wide functions so that each actor’s responsibilities are clearly defined.

### 4.2.1 Tenant Requirements

The tenant role represents users who search for properties, submit rental requests, and manage their rental-related activities. The system shall provide the following tenant functions:

1. The system shall allow tenants to register and log in to the platform using valid credentials.
2. The system shall allow tenants to log out securely after completing their session.
3. The system shall allow tenants to update their private information and change their password.
4. The system shall allow tenants to browse available properties and view detailed property information.
5. The system shall allow tenants to filter properties by relevant criteria such as region, price, area, and layout.
6. The system shall allow tenants to view property photos and supporting information before making a decision.
7. The system shall allow tenants to submit rental requests for selected properties.
8. The system shall allow tenants to track the approval status of their rental requests.
9. The system shall allow tenants to communicate with agents through the messaging feature.
10. The system shall allow tenants to view and download electronic contracts when the rental process is completed.
11. The system shall allow tenants to check rent payment-related information.
12. The system shall allow tenants to submit contract extension requests when needed.

### 4.2.2 Agent Requirements

The agent role represents real estate users who manage property records and handle rental decisions. The system shall provide the following agent functions:

1. The system shall allow agents to log in and log out securely.
2. The system shall allow agents to update their private information and change their password.
3. The system shall allow agents to create, modify, and manage property information.
4. The system shall allow agents to upload and manage property photos.
5. The system shall allow agents to update property availability status, such as to let, rented, and under maintenance.
6. The system shall allow agents to review rental requests submitted by tenants.
7. The system shall allow agents to approve or reject rental requests.
8. The system shall allow agents to manage payment and contract-related processes after request approval.
9. The system shall allow agents to handle contract extension requests.
10. The system shall allow agents to communicate with tenants through the messaging feature.
11. The system shall allow agents to end a rental contract when the rental period is completed or terminated.

### 4.2.3 Admin Requirements

The admin role represents administrative users who supervise the overall operation of the system. The system shall provide the following admin functions:

1. The system shall allow admins to log in and log out securely.
2. The system shall allow admins to update their private information and change their password.
3. The system shall allow admins to manage user access by creating, modifying, or disabling agent and tenant accounts when required.
4. The system shall allow admins to monitor rental requests, transactions, properties, and contract records.
5. The system shall allow admins to access system-wide dashboard information for operational monitoring.
6. The system shall allow admins to review login activity records for audit and traceability purposes.
7. The system shall allow admins to search transaction information by date, transaction number, property, tenant, or agent.

### 4.2.4 System Requirements

In addition to role-specific requirements, the system shall provide the following general functions:

1. The system shall enforce role-based access control so that each user can only access authorized features.
2. The system shall validate input data before storing it in the database.
3. The system shall record successful login activity for audit purposes.
4. The system shall maintain consistent transaction status transitions across the rental workflow.
5. The system shall support structured storage of user, property, request, contract, extension, message, and audit data.
6. The system shall provide a browser/server-based interface that can be accessed through a standard web browser.

The functional requirements above form the basis for the process models, architecture, and database design presented in the following sections of this chapter.

## 4.3 Non-Functional Requirements

The non-functional requirements define the quality attributes that the system must satisfy, including reliability, usability, maintainability, and security. These requirements ensure that the application is not only functionally correct, but also stable and practical for real use.

## 4.4 System Architecture

The system uses a browser/server architecture implemented with Laravel and MySQL. The presentation layer is delivered through web pages, the application layer handles workflow and authorization logic, and the data layer stores relational records for users, properties, rental requests, contracts, extensions, messages, and audit logs.

## 4.5 Use Case Diagram

This section presents the interaction between tenant, agent, and admin actors and the main system services.

## 4.6 Activity Diagram and Explanation

This section presents the workflow diagrams for the core rental processes and supporting functions.

## 4.7 Database Design

This section explains the relational design used to store and connect the data entities required by the system.

## 4.8 Entity Relationship Diagram (ERD)

This section presents the data relationships between the main entities of the rental management system.

## 4.9 Database Table Structures

This section describes the tables, columns, and relationships used in the implementation database.

## 4.10 Design Rationale

This section explains the reasoning behind the selected role structure, workflow control, status transitions, and data organization.
