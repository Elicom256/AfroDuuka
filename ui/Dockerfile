# -----------------------------
# Build stage
# -----------------------------
FROM node:22 AS Frontend-builder

WORKDIR /app

COPY package*.json ./
RUN npm install

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