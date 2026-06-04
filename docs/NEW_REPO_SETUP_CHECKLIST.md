# New Repo Setup Checklist (St Barth)

Use this list to complete wiring for the new stage project.

## 1) GitHub repository

- Repository: stbartvillarentals
- Keep main as deploy branch
- Protect main branch (optional required reviews)

## 2) GitHub Actions secrets

Add these repository secrets:

- SFTP_HOST
- SFTP_USER
- SFTP_PORT
- SFTP_PRIVATE_KEY
- SFTP_PASSPHRASE (optional)

Notes:
- Use an SSH private key dedicated to this repo/stage.
- If possible, keep automation key passphrase-free.

## 3) Deploy workflow values

Update placeholders in .github/workflows/deploy-theme.yml and .github/workflows/ssh-probe.yml:

- CPANEL_USER
- THEME_TARGET
- DEPLOY_TMP_BASE

## 4) Stage WordPress target

Confirm server target paths:

- WordPress root path
- Active child theme directory name (hello-elementor-child)
- WP-CLI path (if not default)

## 5) Install parent + child theme in WordPress

- Ensure Hello Elementor parent theme is installed and active once
- Deploy this repo to wp-content/themes/hello-elementor-child
- Activate child theme in Appearance > Themes

## 6) First validation run

After secrets + workflow values are set:

- Run workflow: Quality (PHP lint)
- Run workflow: SSH Probe
- Run workflow: Deploy Theme (manual dispatch)
- Verify deployed files include style.css and functions.php
- Verify build marker in page source: stbart-build-id

Optional local helpers:

- Run preflight script before triggering actions:
	- `pwsh ./scripts/preflight-check.ps1`
- Trigger all three workflows from CLI (if gh is authenticated):
	- `pwsh ./scripts/run-first-deploy.ps1`

## 7) WordPress connection checks

- Confirm admin URL and login role for deployment verification
- Confirm permalink structure is enabled (Post name)
- Visit /?rmof_flush=1 while logged in as admin, then test front page

## 8) Handoff package

Provide these items:

- Repo URL and branch strategy
- Stage admin URL + WordPress role
- This checklist + deployment secrets list
