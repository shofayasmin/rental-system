# Chapter 5 Implementation and Testing

Chapter 5 presents the implementation of the web-based house rental management system and the testing approach used to verify the implemented functions. This chapter explains how the system was developed in the Laravel framework, how the main features were realized in the application, and how the implemented functions were validated through functional and workflow-based testing.

## 5.1 Development Environment

The system was implemented as a web-based application using Laravel and MySQL in a browser/server architecture. Development was carried out in a local environment using role-based user accounts, namely tenant, agent, and admin. This setup allows the system to be developed and tested in a controlled environment before deployment.

Laravel is used as the main application framework because it supports modular development through controllers, routes, models, middleware, and request validation. MySQL is used as the relational database management system to store user data, property records, rental requests, transactions, contracts, contract extensions, messages, login audit records, and location data. The combination of Laravel and MySQL provides a stable structure for handling the core rental workflow and maintaining data consistency across related modules.

The development environment also supports iterative implementation and testing of the system modules. Each module can be developed independently, verified through functional testing, and then integrated into the complete application. This environment is suitable for a rental management system because it supports role-based access control, transactional workflow management, and structured data storage within one integrated platform.

## 5.2 Implementation of Core Features

This section presents the main features implemented in the House Rental Management System. Each feature corresponds to the functional requirements and process models described in the previous chapters. The implementation is organized according to the main user interactions and system modules so that the overall application flow can be understood clearly.

### 5.2.1 Homepage and Navigation

The homepage serves as the public entry point to the system. It provides access to property browsing and the authentication pages, allowing visitors and tenants to begin using the platform without requiring any prior navigation steps. The homepage also presents the primary navigation structure that directs users to the appropriate section based on their role.

The navigation design is role-aware. Tenants are directed to browsing, request, contract, message, and profile functions. Agents are directed to property management, request review, contract handling, photo management, and messaging. Admins are directed to user monitoring, transaction monitoring, and login audit pages. This structure ensures that users can access the relevant features with minimal interaction.

### 5.2.2 Authentication and Authorization

Authentication is implemented through the Laravel session-based login mechanism. Users provide their email and password, and the system validates the credentials before creating an authenticated session. After login, the system identifies the user role and redirects the user to the appropriate dashboard.

Authorization is enforced through middleware and role checks. Each user can only access the features assigned to their role, which prevents unauthorized access to protected pages. The system also supports secure logout by destroying the active session and redirecting the user back to the login page. In addition, user registration is provided for new tenant accounts.

### 5.2.3 Property Management

The property management module is used by agents to create, edit, view, and delete property records. Each property record contains essential information such as title, address, location, rental price, description, room details, and current status. The module also supports the management of property photos so that tenants can review visual information before submitting a rental request.

The system allows agents to update property availability status to reflect whether a property is to let, rented, or under maintenance. Location data is linked through hierarchical references so that property records can be filtered and displayed consistently according to geographic area. This module provides the data foundation for the property browsing functions used by tenants.

### 5.2.4 Rental Request and Transaction

The rental request module supports the core workflow of tenant-to-agent transaction processing. A tenant can select a property and submit a rental request. The system stores the request with an initial status of `pending_review` so that the agent can review it later.

Agents can review the request and decide whether to approve or reject it. If the request is approved, the system updates the request status to `awaiting_payment` and calculates the payment due date. If the tenant confirms payment before the due date, the system updates the request to `paid` and activates the rental transaction. If the payment deadline is missed, the request is automatically marked as `cancelled_lost`.

The module also supports request cancellation by the tenant or agent when required. These status transitions ensure that the rental workflow remains consistent and that each request follows a controlled lifecycle from submission to completion or cancellation.

### 5.2.5 Contract and Extension

After a payment is successfully confirmed, the system generates or activates the related contract record. The tenant can view and download the contract through the application, while the agent can monitor contract records linked to active rental transactions.

The system also supports contract extension. When a tenant requests an extension, the system creates an extension record and stores it with a pending status. The agent can approve or reject the extension, and the system updates the extension workflow accordingly. If the extension is approved, the payment deadline and contract timeline are adjusted based on the new rental period. The tenant may also cancel an extension request before it is completed.

### 5.2.6 Messaging

The messaging module provides contextual communication between tenants and agents. The system supports conversation-based messaging so that messages can be linked to a property or rental request when needed. This makes the discussion history traceable and easier to manage during the rental process.

The module allows users to send and receive messages, view conversation history, and continue communication within the relevant context. By storing conversation and message records in relational tables, the system keeps communication organized and related to the corresponding rental activity.

### 5.2.7 Profile Management

The profile management module allows authenticated users to update their personal information and change their password. This module helps users maintain accurate account data without needing administrative intervention.

The system validates profile input before saving changes. If the data is valid, the system updates the profile information and keeps the account details synchronized with the latest user input. This module is available to tenants, agents, and admins according to their authenticated access.

### 5.2.8 Admin Monitoring

The admin monitoring module provides visibility over system usage and platform activities. Admin users can monitor user records, view transaction records, and access login audit records. This functionality is intended to support operational supervision and improve accountability across the system.

The login audit records are stored whenever a user logs in successfully. These records include the logged-in user, IP address, browser or device information, and login timestamp. By reviewing these records, the admin can trace access activity and monitor user authentication history.

### 5.2.9 Logout Function

The logout function allows authenticated users to end their session securely. When a user clicks logout, the system invalidates the current session, clears the authentication state, and redirects the user to the login page. This prevents unauthorized reuse of the active session.

## 5.3 System Testing

System testing is performed to verify that the implemented functions behave according to the expected requirements. The testing in this study focuses on functional correctness, workflow consistency, and role-based access control.

### 5.3.1 Functional Testing

Functional testing is used to verify that each module produces the expected output for the given input or action. The test cases cover login, property browsing, rental request submission, request approval, payment confirmation, contract viewing, extension handling, messaging, profile updates, logout, user monitoring, transaction monitoring, and login audit monitoring.

### 5.3.2 Workflow Testing

Workflow testing is used to evaluate the end-to-end rental process from property selection to contract activation or cancellation. This includes scenarios where requests are approved, rejected, cancelled, paid on time, or expired due to missed payment deadlines.

### 5.3.3 Access Control Testing

Access control testing is used to verify that tenants, agents, and admins can only access the modules assigned to their roles. This ensures that unauthorized access to protected pages is prevented by the system middleware and role checks.

## 5.4 Chapter Summary

This chapter presented the implementation of the core modules of the House Rental Management System and the testing approach used to verify the system behavior. The implemented modules show how the rental workflow, contract handling, messaging, profile management, and administrative monitoring are realized in the Laravel-based application. The next chapter discusses the results and interpretation of the implemented system.
