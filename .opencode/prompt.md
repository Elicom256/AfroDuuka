# Containerization

Project structure:

- frontend/ = React SPA (currently developed with Vite and npm run dev)
- backend/ = Laravel API-only application
- I deploy to a single VPS
- I want a fully containerized production setup
- I do NOT want Laravel Sail in production, just leave it for dev
- I want to use Laravel Octane with FrankenPHP instead of Nginx + PHP-FPM inside the backend container
- I want a central Nginx reverse proxy container as the only public-facing service

Target architecture:

Internet
│
Nginx Reverse Proxy
├── React Frontend Container
└── Laravel Octane + FrankenPHP Container
│
PostgreSQL
│
Redis

Requirements:

1. Create a production-ready Docker setup.

2. Create if not created already:
   - docker-compose.yml
   - frontend/Dockerfile
   - backend/Dockerfile
   - proxy/nginx.conf
   - any additional config files required

3. Frontend requirements:
   - Build React using npm run build
   - Use a multi-stage Docker build
   - Serve built files from a lightweight web server
   - SPA routing must work correctly (refreshing /dashboard should not 404)

4. Backend requirements:
   - Use Laravel Octane
   - Use FrankenPHP
   - Install only production dependencies
   - Run composer install --no-dev
   - Optimize autoloading
   - Cache Laravel configuration where appropriate
   - Expose port 8000 internally
   - Start using:
     php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000

5. Database:
   - PostgreSQL container
   - Persistent volume

6. Redis:
   - Redis container
   - Internal only

7. Nginx reverse proxy requirements:
   - Only Nginx exposes ports 80 and 443
   - Frontend available on /
   - Backend available on /api
   - Forward all /api traffic to the Laravel container
   - Include recommended proxy headers
   - Prepare configuration for SSL termination

8. Networking:
   - Frontend, backend, postgres, and redis should not expose public ports
   - Use internal Docker networking

9. Environment variables:
   - Use env files correctly
   - Show examples for local production deployment

10. Health and deployment:

- Add healthchecks where useful
- Use restart policies
- Make deployment resilient to container restarts

11. Security:

- Run containers as non-root where practical
- Minimize image sizes
- Avoid unnecessary packages
- Follow Docker production best practices

12. CI/CD preparation:

- Structure Dockerfiles for efficient layer caching
- Make setup suitable for future GitHub Actions deployment
- Add a scalable and modern git actions

13. Deliverables:

- Complete folder structure
- Full contents of every Dockerfile
- Full docker-compose.yml
- Full nginx configuration
- Any required Laravel Octane or FrankenPHP configuration
- Commands to build and deploy on a fresh Ubuntu VPS

Before generating files, briefly explain the architecture decisions and any tradeoffs.
