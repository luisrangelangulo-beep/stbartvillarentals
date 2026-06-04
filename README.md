# stbartvillarentals

WordPress child theme repository for St Barth Villa Rentals.

## What this repo includes

- Minimal Hello Elementor child theme bootstrap
- GitHub Actions for PHP lint, SSH probe, and deploy
- New repo setup checklist for cPanel + WordPress wiring

## Quick start

1. Add GitHub Actions secrets in this repository:
- SFTP_HOST
- SFTP_USER
- SFTP_PORT
- SFTP_PRIVATE_KEY
- SFTP_PASSPHRASE (optional; only if your SSH key uses one)

2. Update workflow placeholders:
- .github/workflows/deploy-theme.yml
- .github/workflows/ssh-probe.yml

3. Install and activate parent theme on WordPress:
- Install Hello Elementor in wp-admin
- Activate Hello Elementor

4. First deploy:
- Run workflow: Quality (PHP lint)
- Run workflow: SSH Probe
- Run workflow: Deploy Theme (workflow_dispatch)

5. Activate child theme on WordPress:
- Appearance > Themes > Hello Elementor Child - STBART
- Visit /?rmof_flush=1 while logged in as admin

## Notes

- This repo is intended to deploy to wp-content/themes/hello-elementor-child
- Main branch is deploy branch
