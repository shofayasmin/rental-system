# Chapter 1 Introduction

Chapter 1 provides the introductory foundation of this study on a web-based house rental management system. It outlines the current challenges in rental operations, including fragmented data handling, inconsistent transaction tracking, and limited process control across stakeholders. This chapter also presents the research problem formulation, objectives, scope and limitations, and expected benefits as the basis for the subsequent chapters.

## 1.1 Background
House rental activities involve repeated interactions among tenants, property owners/agents, and administrators, including listing publication, request handling, payment confirmation, and contract administration. In many local contexts, rental operations still rely on fragmented channels and manually coordinated records, which reduce process consistency and make transaction monitoring difficult (Obse, 2025; Setiawan et al., 2026).

Prior web-based rental studies indicate that digital rental platforms can simplify search processes, accelerate customer service, and provide clearer property information for prospective tenants (Monteverde et al., 2023; Wahab et al., 2025). However, many implementations remain focused on search or basic booking features and do not yet provide tightly controlled end-to-end transaction states.

From a system security and control perspective, access control remains a critical issue in web applications, especially when policies are weakly documented or inconsistently implemented (Le et al., 2022). For rental transactions, this implies the need for clear role boundaries and explicit state transitions to prevent conflicting actions and improve accountability.

Based on these gaps, this research develops a Laravel-based web application that integrates property management, rental request workflow, payment confirmation, contract generation, contract extension, and contextual communication in one controlled rental lifecycle.

## 1.2 Problem Formulation
This study addresses the following research questions:

1. How can a web-based system integrate listing, request, payment, and contract processes into one consistent rental workflow?
2. How can the system reduce process conflicts and inconsistent status updates during rental transactions?
3. How can role-based access improve control, accountability, and protection against unauthorized actions?
4. How can integrated rental data support more transparent operational monitoring and decision support?

## 1.3 Research Objectives
The objectives of this study are:

1. To design and implement an integrated web-based house rental management system.
2. To implement status-based transaction control for rental requests, payment handling, and contract flow.
3. To enforce role-based access for admin, agent, and tenant operations.
4. To improve data traceability and operational visibility for rental process monitoring.

## 1.4 Scope and Limitation
This study is limited to:

1. Residential house rental operations with three user roles: admin, agent, and tenant.
2. Web-based workflow implementation for listing, request, payment confirmation, and contract administration.
3. Internal transaction status processing and due-date validation without direct integration to external payment gateways.
4. Application-level process control and data recording, excluding legal dispute resolution beyond system logs and status history.

## 1.5 Benefits of the Study
Expected contributions include:

1. Practical contribution: improved consistency and transparency of rental operations.
2. Engineering contribution: a controlled rental lifecycle model with role-based authorization and status governance.
3. Data contribution: structured operational records that can be reused for reporting and future rental analytics.

## References (Chapter 1)

Le, H. T., Shar, L. K., Bianculli, D., Briand, L. C., & Nguyen, C. D. (2022). Automated reverse engineering of role-based access control policies of web applications. *Journal of Systems and Software, 184*, 111109. https://doi.org/10.1016/j.jss.2021.111109

Monteverde, A. L., Maderazo, J. J. S., Cruz, K. C. M., & Magnaye, N. A. (2023). Web-based rental house smart finder using rapid application development basis for evaluation of ISO 205010. *International Journal of Metaverse, 1*(1), 1-4. https://doi.org/10.54536/ijm.v1i1.1464

Obse, Z. G. (2025). Addis Ababa online home rental management system, Ethiopia. *Journal of Electrical Systems and Information Technology, 12*(1). https://doi.org/10.1186/s43067-025-00220-1

Setiawan, A. B., Yuniar, E., Hermansyah, M., Mujiono, M., & Ariyadi, D. J. (2026). Decision support system for selecting the best rental house with Weight Product in Sidokare District, Sidoarjo Regency. *G-Tech: Jurnal Teknologi Terapan, 10*(1), 536-548. https://doi.org/10.70609/g-tech.v10i1.8865

Wahab, Z. D., Hendryadi, D., & Halik, S. A. (2025). House rental information system at Bulukumba Regency with website based. *Journal Informatika, Multimedia, and Infomation, 3*(1), 1-11. https://doi.org/10.61912/lajutek.v3i1.129
