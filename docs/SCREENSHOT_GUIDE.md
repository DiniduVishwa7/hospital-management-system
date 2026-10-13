# MediCore — Portfolio Screenshot Guide

Good screenshots demonstrate the UI and CRUD features, but **do not include real patient information, login credentials, browser profile details, or private URLs**.

## Recommended screenshots

| File to create | Screen | What to show |
| --- | --- | --- |
| `dashboard.png` | Dashboard | Navy sidebar, cards, recent appointments, quick actions |
| `login.png` | Admin login | Split-screen design; leave password field blank |
| `patients.png` | Patient Directory | Search/filter and fictional patient rows, including address |
| `doctors.png` | Doctor Directory | Doctor rows with specialization and actions |
| `appointments.png` | Appointment Directory | Fictional appointment rows, filters, statuses |
| `medical-records.png` | Medical Records | Fictional records list; avoid unnecessary details |
| `mobile.png` | Responsive view | Narrow-screen sidebar and one responsive table/card |

## Capture instructions (Windows / Chrome)

1. Open MediCore in Chrome and sign in using the local test administrator account.
2. Fill the demo database **only with fictional records**. Avoid uploading the actual database export to GitHub.
3. For each screen, close unrelated windows/tabs, hide personal bookmarks if visible, and use Windows **Win + Shift + S** to capture the app content.
4. Save images as **PNG** under the project's `docs/screenshots/` folder using the filenames above.
5. Review each screenshot carefully for sensitive information before staging it in GitHub Desktop.
6. When all images are ready, edit the main `README.md`: replace the Screenshots paragraph with the image links below.

## Markdown to paste into README when ready

```markdown
## Screenshots

### Dashboard
![MediCore Dashboard](docs/screenshots/dashboard.png)

### Admin Login
![MediCore Admin Login](docs/screenshots/login.png)

### Patient Management
![Patient Directory](docs/screenshots/patients.png)

### Doctor Management
![Doctor Directory](docs/screenshots/doctors.png)

### Appointment Management
![Appointment Directory](docs/screenshots/appointments.png)

### Medical Records
![Medical Records Directory](docs/screenshots/medical-records.png)

### Mobile Layout
![Responsive MediCore Interface](docs/screenshots/mobile.png)
```

**Tip:** The screenshot section in the bundled README is deliberately text-only until you've actually added the images, to avoid broken image links on GitHub.
