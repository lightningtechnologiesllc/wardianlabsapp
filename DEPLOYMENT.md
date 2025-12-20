# Deployment Guide - K3s with Helm

This guide covers deploying the WardianApp to a K3s cluster using Helm.

## Prerequisites

- A VPS with at least 2 vCPU and 4GB RAM (Hetzner CX22 recommended)
- A domain pointing to your VPS IP
- A dedicated PostgreSQL server
- Docker installed locally for building images
- Helm 3.x installed locally

## 1. VPS Setup

### Install K3s

```bash
ssh root@your-vps-ip

# Install K3s
curl -sfL https://get.k3s.io | sh -

# Verify installation
kubectl get nodes

# Copy kubeconfig for local access
cat /etc/rancher/k3s/k3s.yaml
# Save this to ~/.kube/config on your local machine (update server IP)
```

### Install cert-manager

```bash
kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.14.0/cert-manager.yaml

# Wait for cert-manager
kubectl wait --for=condition=ready pod -l app=cert-manager -n cert-manager --timeout=120s
```

## 2. Prepare Symfony Secrets

```bash
make exec

# Generate prod encryption keys
bin/console secrets:generate-keys --env=prod

# Set all required secrets
bin/console secrets:set DATABASE_URL --env=prod
bin/console secrets:set DISCORD_CLIENT_SECRET --env=prod
bin/console secrets:set DISCORD_BOT_TOKEN --env=prod
bin/console secrets:set OAUTH_STRIPE_CLIENT_SECRET --env=prod
bin/console secrets:set STRIPE_WEBHOOK_SECRET --env=prod
bin/console secrets:set PLATFORM_STRIPE_WEBHOOK_SECRET --env=prod
bin/console secrets:set DEFAULT_MAILER_DSN --env=prod
bin/console secrets:set APP_SECRET --env=prod
bin/console secrets:set MAIN_DOMAIN --env=prod
bin/console secrets:set DISCORD_CLIENT_ID --env=prod
bin/console secrets:set OAUTH_STRIPE_CLIENT_ID --env=prod

# Verify
bin/console secrets:list --reveal --env=prod
```

Save the decryption key from `config/secrets/prod/prod.decrypt.private.php`.

## 3. Build Docker Images

```bash
# Build PHP image
docker build -t wardianapp-php:latest --target prod -f .docker/php/Dockerfile .

# Build Nginx image (requires PHP image first for assets)
docker build -t wardianapp-nginx:latest --target prod -f .docker/nginx/Dockerfile .
```

### Push to Registry (if using one)

```bash
docker tag wardianapp-php:latest your-registry/wardianapp-php:latest
docker tag wardianapp-nginx:latest your-registry/wardianapp-nginx:latest
docker push your-registry/wardianapp-php:latest
docker push your-registry/wardianapp-nginx:latest
```

### Or build directly on VPS

```bash
rsync -avz --exclude=node_modules --exclude=vendor ./ root@your-vps-ip:/opt/wardianapp/

ssh root@your-vps-ip
cd /opt/wardianapp
docker build -t wardianapp-php:latest --target prod -f .docker/php/Dockerfile .
docker build -t wardianapp-nginx:latest --target prod -f .docker/nginx/Dockerfile .
```

## 4. Deploy with Helm

### Create values file for your environment

```bash
cp helm/wardianapp/values.yaml helm/wardianapp/values-prod.yaml
```

Edit `values-prod.yaml`:

```yaml
images:
  php:
    repository: wardianapp-php  # or your-registry/wardianapp-php
    tag: latest
  nginx:
    repository: wardianapp-nginx  # or your-registry/wardianapp-nginx
    tag: latest

ingress:
  host: app.wardianlabs.com

certManager:
  email: your-email@example.com
```

### Install the chart

```bash
# First install (create namespace)
helm install wardianapp ./helm/wardianapp \
  --namespace wardianapp \
  --create-namespace \
  --values helm/wardianapp/values-prod.yaml \
  --set secrets.symfonyDecryptionSecret="your-decryption-secret"

# Or upgrade existing
helm upgrade wardianapp ./helm/wardianapp \
  --namespace wardianapp \
  --values helm/wardianapp/values-prod.yaml \
  --set secrets.symfonyDecryptionSecret="your-decryption-secret"
```

### Verify deployment

```bash
# Check release status
helm status wardianapp -n wardianapp

# Check pods
kubectl get pods -n wardianapp

# Check logs
kubectl logs -f deployment/wardianapp -c php -n wardianapp
kubectl logs -f deployment/wardianapp -c nginx -n wardianapp

# Check ingress and certificate
kubectl get ingress -n wardianapp
kubectl get certificate -n wardianapp
```

## 5. Run Migrations

```bash
kubectl exec -it deployment/wardianapp -c php -n wardianapp -- bin/console doctrine:migrations:migrate --no-interaction
```

## 6. DNS Configuration

Point your domain to your VPS IP:

```
app.wardianlabs.com  A  YOUR_VPS_IP
```

## Updating the Application

```bash
# Build new images
docker build -t wardianapp-php:v1.2.0 --target prod -f .docker/php/Dockerfile .
docker build -t wardianapp-nginx:v1.2.0 --target prod -f .docker/nginx/Dockerfile .

# Update with Helm
helm upgrade wardianapp ./helm/wardianapp \
  --namespace wardianapp \
  --values helm/wardianapp/values-prod.yaml \
  --set images.php.tag=v1.2.0 \
  --set images.nginx.tag=v1.2.0 \
  --set secrets.symfonyDecryptionSecret="your-decryption-secret"
```

## Useful Commands

```bash
# Get shell in PHP container
kubectl exec -it deployment/wardianapp -c php -n wardianapp -- bash

# View logs
kubectl logs -f deployment/wardianapp -c php -n wardianapp
kubectl logs -f deployment/wardianapp -c nginx -n wardianapp

# Check Helm releases
helm list -n wardianapp

# Rollback to previous version
helm rollback wardianapp -n wardianapp

# Uninstall
helm uninstall wardianapp -n wardianapp

# Dry-run to see rendered templates
helm template wardianapp ./helm/wardianapp --values helm/wardianapp/values-prod.yaml
```

## Cost Estimate

| Resource | Provider | Cost |
|----------|----------|------|
| VPS (2 vCPU, 4GB) | Hetzner CX22 | ~€6.90/mo |
| PostgreSQL Server | Hetzner CX11 | ~€4.15/mo |
| Domain | - | ~€10/year |
| **Total** | | **~€12/mo** |
