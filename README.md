# Cashflow

A personal finance management application to track and control your income, expenses, and overall cashflow. Built with Laravel and Vue 3 for a modern, reactive user experience.

## 🌟 About

Cashflow is a web-based financial tracking system designed for personal use. While created for my own financial management needs, I'm making it available as an open-source project so others can benefit from it, customize it, or fork it for their own purposes.

## ✨ Features

- 💰 Track income and expenses
- 📊 Categorize transactions
- 🔐 Secure authentication with Laravel Fortify
- 📱 Modern, responsive UI
- ⚡ Fast and reactive interface using Inertia.js
- 🎨 Beautiful UI components with Reka UI and Tailwind CSS

## 🛠️ Tech Stack

### Backend
- **Laravel 12** - PHP framework
- **Laravel Fortify** - Authentication
- **Laravel Wayfinder** - Type-safe routing
- **MySQL/PostgreSQL** - Database (configurable)

### Frontend
- **Vue 3** - JavaScript framework
- **TypeScript** - Type safety
- **Inertia.js** - Modern monolith approach
- **Tailwind CSS 4** - Utility-first CSS
- **Reka UI** - Unstyled, accessible components
- **Lucide Icons** - Beautiful icon set
- **TanStack Table** - Powerful table component
- **VueUse** - Vue composition utilities

## 📋 Prerequisites

Before you begin, ensure you have the following installed:
- PHP 8.2 or higher
- Composer
- Node.js 18 or higher
- npm or yarn
- MySQL, PostgreSQL, or SQLite

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/cashflow.git
   cd cashflow
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your database**
   
   Edit the `.env` file and update the database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=cashflow
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

## 🏃 Running the Application

### Development

You can use the provided composer scripts for easy development:

```bash
# Start both Laravel and Vite dev servers
composer dev
```

Or run them separately:

```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Vite development server
npm run dev
```

The application will be available at `http://localhost:8000`

### Production

For production deployment:

```bash
# Build frontend assets with SSR support
npm run build:ssr

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📁 Project Structure

```
├── app/
│   ├── Models/          # Eloquent models (User, Category, Transaction)
│   ├── Http/            # Controllers, Middleware, Requests
│   └── Providers/       # Service providers
├── database/
│   ├── migrations/      # Database migrations
│   └── seeders/         # Database seeders
├── resources/
│   ├── js/              # Vue components and TypeScript code
│   │   ├── components/  # Reusable Vue components
│   │   ├── layouts/     # Layout components
│   │   ├── pages/       # Page components (Inertia)
│   │   └── types/       # TypeScript type definitions
│   ├── css/             # Stylesheets
│   └── views/           # Blade templates
└── routes/              # Application routes
```

## 🧪 Testing

Run the test suite:

```bash
# Run all tests
php artisan test

# Run tests with coverage
php artisan test --coverage
```

## 🎨 Code Style

This project uses Laravel Pint for PHP and ESLint/Prettier for JavaScript/TypeScript:

```bash
# Format PHP code
./vendor/bin/pint

# Format JavaScript/TypeScript code
npm run format

# Lint JavaScript/TypeScript code
npm run lint
```

## 🤝 Contributing

While this is primarily a personal project, contributions are welcome! Here's how you can help:

1. Fork the repository
2. Create a new branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Commit your changes (`git commit -m 'Add some amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

Please ensure your code follows the existing style guidelines and includes appropriate tests.

## 📝 License

This project is open-source and available under the [MIT License](LICENSE).

## 🙏 Acknowledgments

- Built with [Laravel](https://laravel.com/)
- UI powered by [Vue.js](https://vuejs.org/)
- Styled with [Tailwind CSS](https://tailwindcss.com/)
- Icons from [Lucide](https://lucide.dev/)

## 📧 Contact

If you have any questions or suggestions, feel free to open an issue on GitHub.

---

**Note**: This is a personal project made available for public use. Use at your own risk and always backup your financial data regularly.