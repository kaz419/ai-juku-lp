# Deployment notes (mixhost + GitHub + Airtable)

## 1) Required environment variables on mixhost
Set these in your hosting env (or Apache SetEnv / secure server config):

- `AIRTABLE_TOKEN`
- `AIRTABLE_BASE_ID`
- `AIRTABLE_TABLE` (optional, default: `Leads`)

## 2) Airtable table fields (recommended)
Create a table `Leads` with these columns:

- `created_at` (single line text or date)
- `company`
- `name`
- `email`
- `role`
- `industry`
- `inquiry_type`
- `team_size`
- `phone`
- `message`
- `source_url`
- `utm_source`
- `utm_medium`
- `utm_campaign`

## 3) Git deployment flow
- Push to GitHub (`main`)
- On mixhost, pull latest from Git
- Verify:
  - `/contact.html` renders form
  - `POST /api/lead.php` returns `{ ok: true }`

## 4) Optional
- Connect Airtable Automation for auto-reply email
- Add GA4 events (`form_start`, `form_submit`, `form_success`)
