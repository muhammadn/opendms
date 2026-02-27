# Hybrid Deployment: Store-and-Forward with Laravel Outbox

## Overview

OpenDMS supports three deployment modes, controlled entirely via the `.env` file:

| Mode | `DB_CONNECTION` | `CENTRAL_DMS_URL` | Description |
|---|---|---|---|
| **Production** | `mysql` / `pgsql` | *(empty)* | This instance IS the central server |
| **Offline standalone** | `sqlite` | *(empty)* | No internet; field team only |
| **Hybrid** | `sqlite` | `https://...` | Field node syncs to central when online |

In hybrid mode, the system uses a **store-and-forward outbox pattern**: every incoming
LoRa message is written to the local SQLite database first, then a background queue
worker attempts to push the record upstream to the central server. The worker retries
indefinitely with exponential backoff, so data accumulated during outages is
automatically delivered when connectivity returns — no operator intervention needed.

---

## How It Works

```
MamaDuck (LoRa)
      │
PapaDuck Gateway (SX1302)
      │ MQTT
      ▼
ProcessMqttMessage (Laravel Job)
      │
      ├─── ClusterData::create()          ← written locally first, always succeeds
      │         synced = false            ← marks record as pending sync
      │
      └─── SyncRecordToCloud::dispatch()  ← queued in same SQLite jobs table
                    │
             queue:work (background process)
                    │
             CENTRAL_DMS_URL set?
             ├─ YES + internet up  ──► POST /api/ingest  ──► synced = true
             └─ YES + offline      ──► retry (30s → 60s → 2m → 5m → 10m)
```

The `synced` column on `cluster_data` has three states:

| Value | Meaning |
|---|---|
| `null` | Sync not applicable (production or fully offline mode) |
| `false` | Pending sync (hybrid mode, not yet delivered to central) |
| `true` | Successfully delivered to central server |

This means the same schema and codebase works in all three modes with no data model changes.

---

## Setup

### 1. Run the migration

```bash
php artisan migrate
```

This adds `synced` (nullable boolean) and `synced_at` (nullable timestamp) to `cluster_data`.

### 2. Configure `.env` on the field node

```env
# Field node running SQLite
DB_CONNECTION=sqlite
DB_DATABASE=/home/pi/opendms/database/field.sqlite
QUEUE_CONNECTION=database

# Central server to sync to (leave empty for offline standalone)
CENTRAL_DMS_URL=https://dms.example.gov.my
CENTRAL_DMS_TOKEN=your-pre-shared-api-token
```

Leave `CENTRAL_DMS_URL` empty (or remove it) for:
- The central production server itself
- A fully offline standalone deployment with no central server

### 3. Install the systemd service

Edit `deploy/systemd/opendms-queue.service` and update `User` and `WorkingDirectory`
to match your deployment path, then:

```bash
sudo cp deploy/systemd/opendms-queue.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable opendms-queue.service
sudo systemctl start opendms-queue.service
```

Check the worker is running:

```bash
sudo systemctl status opendms-queue.service
journalctl -u opendms-queue -f
```

---

## Monitoring Sync State

Count records still pending upload:

```bash
php artisan tinker --execute="echo App\Models\ClusterData::where('synced', false)->count();"
```

Count records successfully synced:

```bash
php artisan tinker --execute="echo App\Models\ClusterData::where('synced', true)->count();"
```

Or add a simple dashboard widget by querying:

```php
ClusterData::selectRaw("
    COUNT(*) FILTER (WHERE synced IS NULL)   AS not_applicable,
    COUNT(*) FILTER (WHERE synced = 0)       AS pending,
    COUNT(*) FILTER (WHERE synced = 1)       AS synced
")->first();
```

---

## Central Server: Ingestion Endpoint

The central DMS must expose a `POST /api/ingest` endpoint authenticated by Bearer token.
The payload is the raw `ClusterData` column values as JSON. The field node's
`SyncRecordToCloud` job considers any `2xx` response as success.

The central server should be another OpenDMS instance running in production mode
(`DB_CONNECTION=mysql` or `pgsql`, `CENTRAL_DMS_URL` empty).

---

## Why Not MQTT Bridge?

Mosquitto's bridge feature can forward messages between brokers and is a natural
alternative. However:

- Mosquitto's persistence queue is a **separate file** from your application data.
  A misconfigured or crashed broker during an outage can silently drop messages.
- There is no per-record audit trail (no `synced`/`synced_at` in the database).
- The central server would need a publicly exposed MQTT broker port (1883/8883),
  which is commonly blocked by firewalls and cloud providers.
- The Laravel queue approach uses only **HTTPS (port 443)** for delivery —
  universally allowed through any NAT, firewall, or satellite link.

The Laravel database queue stores durability in the same SQLite file as your
application data: one file, one backup, one source of truth.

---

## Rollback

To remove the sync columns (e.g., if reverting to offline-only):

```bash
php artisan migrate:rollback --path=database/migrations/2026_02_27_000000_add_sync_columns_to_cluster_data_table.php
```
