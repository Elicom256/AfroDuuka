# Technical Constraints

- The app is running in docker, dcdev alias
- All tests are done inside docker environment
- Follow the application's system design
- Do not hallucinate or introduce new business rules.
- Preserve the existing architecture, design patterns, and workflows.
- Reuse existing services, models, events, observers, and relationships where possible.
- Edit migrations directly if schema changes are required.
- Avoid duplicate revenue calculations from multiple sources.
- Ensure all calculations remain transactional and consistent across the system.
- Use ShadCN components for new UI elements.
- Use Lucide React icons where appropriate.
- Use Chart.js for all analytics visualizations.
