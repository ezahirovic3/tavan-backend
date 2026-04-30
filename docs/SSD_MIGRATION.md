# Moving Project + Docker to External SSD

## Overview

Two things need to move:
1. **Docker Desktop's virtual disk** (where all container data, images, and volumes live) — this is the big one, typically 20–60GB
2. **The project folder** (`tavan-backend`) — small, just code + vendor

## Step 1 — Format the SSD (if new)

On Mac, open **Disk Utility** → select the SSD → Erase:
- Format: `APFS`
- Scheme: `GUID Partition Map`

Name it something simple, e.g. `SSD`. It will mount at `/Volumes/SSD`.

## Step 2 — Move Docker's virtual disk

Docker Desktop on Mac stores everything in a single virtual disk file (`.raw` or `.qcow2`). You're moving that file to the SSD.

1. **Quit Docker Desktop** completely (menubar icon → Quit Docker Desktop)

2. Open **Docker Desktop** → Settings → **Resources** → **Advanced**

3. Change **Disk image location** to `/Volumes/SSD/Docker`

4. Click **Apply & Restart**

Docker will move its entire virtual disk to the SSD automatically. This may take a few minutes depending on how much data is there.

> After this, all future Docker images, containers, and volumes are stored on the SSD. Your Mac's internal storage is freed.

## Step 3 — Move the project folders

Once Docker is moved and running, move the project folders:

```bash
# Move backend project
mv ~/Desktop/tavan-backend /Volumes/SSD/tavan-backend

# Move mobile project (optional, also takes space via node_modules)
mv ~/Desktop/tavan-mobile /Volumes/SSD/tavan-mobile
```

## Step 4 — Update the Sail alias (if you have one)

If you added a `sail` shell alias, update the path. Otherwise just use the full path:

```bash
/Volumes/SSD/tavan-backend/vendor/bin/sail up -d
```

Or `cd` into the project first:

```bash
cd /Volumes/SSD/tavan-backend
./vendor/bin/sail up -d
```

## Step 5 — Verify everything works

```bash
cd /Volumes/SSD/tavan-backend
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:status
```

Visit `http://localhost` — should respond normally.

## Important notes

- **Always plug in the SSD before opening Docker Desktop.** If Docker starts and the disk image is missing (SSD unplugged), it'll error. Just plug in → restart Docker.
- The SSD should be formatted as **APFS**, not exFAT or FAT32 — Docker needs Unix file permissions.
- If you use an IDE (VS Code, Cursor), re-open the project from the new SSD path.
- Git remotes are unaffected — same repo, just a different local path.

## Storage estimate

| Thing | Approx size |
|-------|-------------|
| Docker images (PHP, MySQL, Redis) | ~3–5 GB |
| MySQL data volume (grows over time) | ~500 MB initially |
| Project code + vendor | ~200 MB |
| **Total** | **~4–6 GB** to start |

A 256 GB SSD is more than enough for the entire dev environment.
