# Order Printer for Shopware 6

A Symfony-based microservice that automatically fetches open orders from Shopware 6 and prints them using ESC/POS compatible printers (via CUPS or direct network/USB).

## Features

- **Automated Polling**: Uses Symfony Scheduler to check for new orders every 10 seconds.
- **Shopware Integration**: Uses the Shopware SDK to fetch order details.
- **ESC/POS Support**: Generates formatted receipts for thermal printers.
- **Asynchronous Processing**: Uses Symfony Messenger for reliable print job handling.

## Requirements

- PHP 8.5 or higher
- SQLite extension (for queue and local storage)
- CUPS (if printing via local system printer) or a networked ESC/POS printer.

## Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/your-username/order-printer.git
   cd order-printer
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Configure environment**:
   Copy the example environment file and fill in your credentials:
   ```bash
   cp .env .env.local
   ```
   Edit `.env.local` and provide your Shopware API credentials and printer name.

4. **Initialize database**:
   ```bash
   bin/console doctrine:database:create
   ```

## Configuration

The following environment variables are required in your `.env.local`:

- `SHOPWARE_HOST`: Your Shopware 6 store URL.
- `SHOPWARE_CLIENT_ID`: Integration Client ID.
- `SHOPWARE_CLIENT_SECRET`: Integration Client Secret.
- `PRINTER_NAME`: The name of your printer as recognized by the system.

## Usage

Start the messenger worker and the scheduler:

```bash
# Run the scheduler to poll for orders
bin/console messenger:consume scheduler_default

# Run the worker to process print jobs
bin/console messenger:consume async
```
