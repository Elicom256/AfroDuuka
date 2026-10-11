# -----------------------------
# Build stage
# -----------------------------
FROM node:22-alpine AS Frontend-builder

WORKDIR /app

ARG VITE_BASE_URL
ENV VITE_BASE_URL=$VITE_BASE_URL

# Fail the build loudly if a deploy forgot the API origin: baking the default
# http://localhost/api into the bundle would otherwise ship a broken app.
RUN test -n "$VITE_BASE_URL" \
    || (echo "ERROR: VITE_BASE_URL build arg is required (e.g. --build-arg VITE_BASE_URL=https://afroduuka-api.onrender.com/api)" && exit 1)

COPY package*.json ./
RUN npm ci

COPY . .

RUN npm run build


# -----------------------------
# Production stage (Nginx)
# -----------------------------
FROM nginx:alpine

COPY nginx.conf /etc/nginx/conf.d/default.conf

# Match the stage alias (Frontend-builder)
COPY --from=Frontend-builder /app/dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
