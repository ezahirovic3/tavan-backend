# Tavan Backend

Laravel 11 REST API for the Tavan secondhand fashion marketplace.

---

## How it runs

The backend uses **Laravel Sail** — a Docker wrapper that starts three containers together:

| Container | Role | Local port |
|---|---|---|
| `laravel.test` | PHP + Nginx (the actual API) | `http://localhost` |
| `mysql` | MySQL 8 database | `localhost:3306` |
| `redis` | Cache + queue | `localhost:6379` |

**Docker Desktop must be running** before you use any `sail` command.

---

## Starting and stopping

Open the `tavan-backend` folder in VSCode (or any terminal), then:

```bash
# Start everything in the background
./vendor/bin/sail up -d

# Stop everything
./vendor/bin/sail down
```

Once started, the API is live at:
- **API** → `http://localhost/api/v1/...`
- **Telescope** (request inspector) → `http://localhost/telescope`
- **API docs** → `http://localhost/docs/api`

---

## Common commands

```bash
# Run pending migrations
./vendor/bin/sail artisan migrate

# Fresh wipe + reseed (resets to mock-equivalent dev data)
./vendor/bin/sail artisan migrate:fresh --seed

# Tail the Laravel log in real time
./vendor/bin/sail artisan pail

# Open an interactive PHP console
./vendor/bin/sail artisan tinker

# Check running containers
./vendor/bin/sail ps
```

---

## Seed accounts

After `migrate:fresh --seed` these accounts are available:

| Email | Password | Role |
|---|---|---|
| `test@tavan.ba` | `test123` | Main test user (power seller) |
| `amira@tavan.ba` | `lozinka123` | Seller — Mostar |
| `lejla@tavan.ba` | `lozinka123` | Seller — Tuzla |
| `kenan@tavan.ba` | `lozinka123` | Buyer — Sarajevo |
| `sara@tavan.ba` | `lozinka123` | Buyer — Banja Luka |
| `admin@tavan.store` | `password` | Filament admin panel |

---

## Optional: shorter alias

Add this to your `~/.zshrc` so you can type `sail` instead of `./vendor/bin/sail`:

```bash
alias sail='./vendor/bin/sail'
```

Then reload: `source ~/.zshrc`

---

## Mobile app

The React Native / Expo app lives at `/Volumes/SSD/tavan-mobile`.
It talks to this API via `http://localhost/api/v1` in dev.
