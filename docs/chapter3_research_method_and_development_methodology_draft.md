# Chapter 3 Research Method and Development Methodology

Chapter 3 presents the research method and development methodology used in this study. The chapter explains how the study was conducted, how the system requirements were collected, and how the web-based house rental management system was developed through an iterative prototyping approach. This chapter functions as the methodological foundation for the rest of the thesis because it connects the research problem, the data collection process, and the software development strategy into one coherent flow.

## 3.1 Research Method

This study uses an applied research approach because the main objective is not only to analyze the rental problem, but also to produce a working software solution that can address it. The research is therefore oriented toward practical system development rather than toward theoretical explanation alone. In this project, the outcome expected from the research is a web-based house rental management system that can improve process consistency, role accountability, and transaction traceability in rental operations.

The method is aligned with research and development practice, where a study begins with problem identification, continues with system analysis and design, proceeds to implementation, and ends with validation of the developed artifact. This approach is suitable for a rental system because the application is intended to support a real operational workflow involving multiple users, changing transaction states, and controlled access boundaries. As a result, the study does not treat the system as a static product, but as an evolving artifact that can be refined through repeated development cycles.

The use of an applied research method is also consistent with prior rental system studies that use prototyping-oriented approaches to support gradual refinement of the system (Monteverde et al., 2023; Obse, 2025; Riyanti et al., 2024). These studies show that rental applications are often more effective when the development process allows feedback and revision during implementation. Based on that context, this thesis adopts a development-oriented research method that is suitable for a web application with role-based workflow control.

In practical terms, the research method provides a structured path from the identification of the rental problem to the production of a final system artifact. It ensures that each stage of the thesis is connected to the actual system being built, so the written documentation remains consistent with the implementation process.

## 3.2 Data Collection Technique

The data collection stage was carried out to understand the real needs of the rental system and to define the features that must be included in the application. Because this study is development-based, the collected data is not used only to describe the problem, but also to shape the system requirements and guide the design process. The data collection process is therefore a critical stage that connects the research context with the technical solution.

The techniques used in this study are as follows.

1. Literature study
   Literature study was used to collect theoretical and empirical references related to house rental systems, role-based access control, rental transaction workflows, and iterative development methods. Through this technique, the research can position the proposed system within existing academic work and identify what has already been studied in similar domains. The literature review also helps justify the use of prototyping and role-based design choices in the development of the system.

2. Observation
   Observation was used to understand the rental workflow that the system must support, including property listing, rental request submission, approval process, payment handling, contract flow, and communication between tenant and agent. This technique is important because it helps capture the sequence of operational activities that occur in rental management. By observing the workflow, the study can determine how the system should behave in real use and which parts of the process require tighter control or clearer structure.

3. Document analysis
   Document analysis was used to study project materials such as the initial project assignment, the initial project report, the dissertation project assignment, source code structure, migration files, database schema, and feature documentation already prepared in the project folder. This technique helps map the actual implementation context and ensures that the methodology is consistent with the software artifact being developed. It is particularly useful for distinguishing between the initial lecturer-provided requirements, the baseline prototype documented in the project report, and the additional features added during later development.

Together, these techniques support the analysis of user roles, workflow states, and data structures that are needed for the final system design. They also help maintain alignment between the research documentation and the software implementation so that the thesis remains grounded in the real project rather than in abstract assumptions.

## 3.3 Development Methodology

The development methodology used in this study is Iterative Prototyping. This method is selected because the system requirements are best validated through repeated refinement cycles. The rental workflow involves multiple roles and several dependent processes, so the application needs to be built step by step and reviewed after each iteration. Instead of waiting until all features are finished before evaluating the system, the prototype approach allows early testing of essential functions and gradual correction of design problems.

This methodology is especially suitable for a house rental management system because changes in one module can affect other modules. For example, adjustments in request handling may affect payment flow, contract generation, or messaging. By developing the system iteratively, the study can reduce the risk of design mismatch, detect workflow problems earlier, and keep the application consistent as it expands.

### 3.3.1 Iterative Prototyping

Iterative Prototyping is a development approach in which a working version of the system is created early and then improved continuously based on evaluation results. In this study, the method is used to develop the rental management system from an initial working prototype into a more complete final version. The development is not described through formal software release numbers; instead, it is described through the evolution of the implemented features and workflow refinements.

The development process began with the project assignment provided by the lecturer, which defined the project category as a software design and implementation project with the title House Rental Management System. The assignment already specified the core requirements, including user login and logout, password changes, profile updates, login activity logging, role-based users, property management, rental requests, transaction tracking, and other related rental functions. The first working prototype was then documented in the initial project report as the baseline implementation of this initial scope.

After the baseline prototype was submitted, the system was further refined through additional development. One later dissertation project assignment introduced the analytics dashboard as an additional formal requirement after it was proposed by the student and approved by the supervisor, while several other enhancements, such as contract extension handling and property availability cycle control, were added during the development process as self-initiated improvements. The role-based access control and status-driven workflow were also refined to improve consistency, traceability, and data integrity across the system. In addition, the transaction information was organized so that rental records could be searched by date, transaction number, property, tenant, or agent, consistent with the project requirements. The approved implementation uses Laravel with a browser/server architecture, so the earlier client/server and Java Swing wording is treated as background specification rather than the final implementation choice.

This approach is supported by previous studies on rental system development and web-based information systems, where prototyping helps developers adjust the system to changing requirements and operational feedback (Monteverde et al., 2023; Obse, 2025; Riyanti et al., 2024). In addition, role-based security requirements and structured workflow control are important in rental applications because incorrect access or inconsistent status transitions can affect data integrity and process reliability (Le et al., 2022; Wahab et al., 2025). These findings indicate that a rental platform benefits from a method that allows repeated correction, especially when the system contains transaction states that must remain consistent.

For this thesis, Iterative Prototyping is preferred over a fully linear model because the system includes several interdependent modules, such as property management, request handling, payment status control, contract generation, contract extension, and messaging. Each module can be built and reviewed before the next refinement is added, which makes the development process more practical and easier to revise. The method also makes it easier to align the thesis narrative with the actual progress of development, since the system evolves through clearly identifiable improvements rather than through a single final implementation step.

### 3.3.2 Development Stages

The development process in this study is divided into the following stages.

1. Requirement identification
   The system needs were identified from the lecturer-provided project assignment, the rental workflow, and the research references. At this stage, the main user roles and core processes were defined so the development could focus on functions that are directly relevant to the rental management problem. This stage provides the basis for deciding what the system must support and what should be excluded from the scope.

2. Initial prototype planning
   A preliminary system structure was prepared, including the main modules, role boundaries, and high-level data flow. This stage defines the first version of the system before implementation begins. The planning phase is important because it organizes the application into clear parts and prevents the development process from becoming fragmented.

3. Prototype implementation
   The first working version of the application was developed based on the identified requirements. The implementation focuses on the core rental functions and the role-based access structure. In this project, the early prototype documented in the initial project report served as the baseline version before the system was expanded with additional modules and refinements.

4. Evaluation and revision
   Each prototype iteration is reviewed to identify missing functions, unclear flows, or inconsistent status transitions. The prototype is then refined based on the evaluation results. In the current project, this revision stage includes improvements such as the analytics dashboard, contract extension handling, property availability cycle control, and transaction search and reporting support. This stage is the core of the iterative method because it turns feedback into concrete improvements and helps the system move closer to the intended behavior.

5. Module integration
   After the individual modules are improved, they are integrated into one complete system so the full rental workflow can be executed in sequence. Integration ensures that the modules do not function as separate parts, but instead operate as one coherent application that supports end-to-end rental processing.

6. Final preparation
   The final prototype is prepared for the implementation and testing stage described in the following chapters. At this point, the system structure, user roles, and data flow are stabilized for documentation and verification. This stage marks the transition from development work to formal evaluation and thesis presentation.

## 3.4 Chapter Summary

This chapter explained the research method, data collection techniques, and the development methodology used in the study. The selected approach combines applied research with iterative prototyping so the system can be developed and refined in a controlled way. It also clarifies how the project requirements were gathered and how the system was incrementally built to match the rental workflow. The next chapter presents the system analysis and design based on the method described here.

## References

Le, H. T., Shar, L. K., Bianculli, D., Briand, L. C., & Nguyen, C. D. (2022). Automated reverse engineering of role-based access control policies of web applications. *Journal of Systems and Software, 184*, 111109. https://doi.org/10.1016/j.jss.2021.111109

Monteverde, A. L., Maderazo, J. J. S., Cruz, K. C. M., & Magnaye, N. A. (2023). Web-based rental house smart finder using rapid application development basis for evaluation of ISO 205010. *International Journal of Metaverse, 1*(1), 1-4. https://doi.org/10.54536/ijm.v1i1.1464

Obse, Z. G. (2025). Addis Ababa online home rental management system, Ethiopia. *Journal of Electrical Systems and Information Technology, 12*(1). https://doi.org/10.1186/s43067-025-00220-1

Riyanti, A., Taryana, T., Dirgantoro, G. P., & Gunawan, I. M. A. O. (2024). Development of rental application using prototyping method. *TECHNOVATE: Journal of Information Technology and Strategic Innovation Management, 1*(2), 69-80. https://doi.org/10.52432/technovate.1.2.2024.69-80

Setiawan, A. B., Yuniar, E., Hermansyah, M., Mujiono, M., & Ariyadi, D. J. (2026). Decision support system for selecting the best rental house with Weight Product in Sidokare District, Sidoarjo Regency. *G-Tech: Jurnal Teknologi Terapan, 10*(1), 536-548. https://doi.org/10.70609/g-tech.v10i1.8865

Wahab, Z. D., Hendryadi, D., & Halik, S. A. (2025). House rental information system at Bulukumba Regency with website based. *Journal Informatika, Multimedia, and Infomation, 3*(1), 1-11. https://doi.org/10.61912/lajutek.v3i1.129
