# MediCore — Portfolio Screenshot Guide

Good screenshots demonstrate the UI and CRUD features, but **do not include real patient information, login credentials, browser profile details, or private URLs**.

The seven screenshots already exist under `docs/screenshots/` and are linked from the [README](../README.md). The folder restructuring preserves their filenames, contents, locations, and existing README image links.

## Existing screenshots

| Filename | Screen | What to show |
| --- | --- | --- |
| `dashboard.png` | Dashboard | Navy sidebar, cards, recent appointments, quick actions |
| `login.png` | Admin login | Split-screen design; leave password field blank |
| `patients.png` | Patient Directory | Search/filter and fictional patient rows, including address |
| `doctors.png` | Doctor Directory | Doctor rows with specialization and actions |
| `appointments.png` | Appointment Directory | Fictional appointment rows, filters, statuses |
| `medical-records.png` | Medical Records | Fictional records list; avoid unnecessary details |
| `mobile.png` | Responsive view | Narrow-screen sidebar and one responsive table/card |

## Capture instructions (Windows / Chrome)

1. Open `http://localhost/hospital-management-system/login.php` in Chrome and sign in using an administrator account created locally. Use the original root page URLs; internal `modules/` pages are blocked by Apache.
2. Fill the demo database **only with fictional records**. Avoid uploading the actual database export to GitHub.
3. For each screen, close unrelated windows/tabs, hide personal bookmarks if visible, and use Windows **Win + Shift + S** to capture the app content.
4. Save images as **PNG** under the project's `docs/screenshots/` folder using the filenames above.
5. Review each screenshot carefully for sensitive information before staging it in GitHub Desktop.
6. For any separately approved future screenshot update, keep the existing README links and filenames. This documentation update does not require recapturing or replacing any images.

## README image-link reference

The following example paths are relative to the root `README.md`, not this guide. The README already contains the screenshot links.

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

**Tip:** Preview the README on GitHub after documentation changes to confirm all seven existing images render. See [Local Setup](LOCAL_SETUP.md) if the application is not running.
