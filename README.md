# Laravel API with JWT Authentication & Role-Based Access

> Complete REST API with JWT authentication, role-based access control, and repository pattern

## Setup JWT Authentication

### 1. Install JWT Package

```bash
composer require tymon/jwt-auth
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```

### 3. Generate JWT Secret

```bash
php artisan jwt:secret
```

### 4. Configure Authentication Guard

Update `config/auth.php`:

```php
'defaults' => [
    'guard' => env('AUTH_GUARD', 'api'),
    'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

## Setup Role-Based Access

### 5. Create Role Migration

```bash
php artisan make:migration create_roles_table
```

### 6. Create Role Model & Seeder

```bash
php artisan make:model Role
php artisan make:seeder RoleSeeder
```

### 7. Create Role Middleware

```bash
php artisan make:middleware RoleMiddleware
```

### 8. Run Migrations & Seed Data

```bash
php artisan migrate
php artisan db:seed
```

## API Endpoints

### Authentication

- `POST /api/register` - Register user
- `POST /api/login` - Login user
- `GET /api/me` - Get current user
- `POST /api/logout` - Logout user

### Products (Role-Protected)

- `GET /api/products` - List products (authenticated)
- `GET /api/products/{id}` - Show product (authenticated)
- `POST /api/products` - Create product (admin only)
- `PUT /api/products/{id}` - Update product (admin only)
- `DELETE /api/products/{id}` - Delete product (admin only)

## Usage

1. **Register/Login** to get JWT token
2. **Include token** in requests: `Authorization: Bearer {token}`
3. **Roles**: admin (full access), user (read-only)

### Example Requests

**Register:**

```json
POST /api/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "role_id": 1
}
```

**Login:**

```json
POST /api/login
{
    "email": "john@example.com",
    "password": "password"
}
```

## Adding New Tables (Step-by-Step)

### 1. Create Migration

```bash
php artisan make:migration create_table_name_table
```

### 2. Define Migration Schema

```php
// database/migrations/xxxx_create_table_name_table.php
Schema::create('table_name', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->foreignId('related_id')->constrained('related_table');
    $table->timestamps();
});
```

### 3. Create Model

```bash
php artisan make:model ModelName
```

```php
// app/Models/ModelName.php
class ModelName extends Model
{
    protected $fillable = ['name', 'description', 'related_id'];
    
    public function related()
    {
        return $this->belongsTo(RelatedModel::class);
    }
}
```

### 4. Create Repository Interface

```php
// app/Repositories/ModelRepositoryInterface.php
interface ModelRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?ModelName;
    public function create(array $data): ModelName;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
```

### 5. Create Repository Implementation

```php
// app/Repositories/Impl/ModelRepository.php
class ModelRepository implements ModelRepositoryInterface
{
    public function all(): Collection
    {
        return ModelName::all();
    }
    
    public function find(int $id): ?ModelName
    {
        return ModelName::find($id);
    }
    
    public function create(array $data): ModelName
    {
        return ModelName::create($data);
    }
    
    public function update(int $id, array $data): bool
    {
        $model = ModelName::find($id);
        return $model ? $model->update($data) : false;
    }
    
    public function delete(int $id): bool
    {
        $model = ModelName::find($id);
        return $model ? $model->delete() : false;
    }
}
```

### 6. Create Service

```php
// app/Services/ModelService.php
class ModelService
{
    public function __construct(protected ModelRepositoryInterface $repository) {}
    
    public function getAll(): Collection
    {
        return $this->repository->all();
    }
    
    public function getById(int $id)
    {
        return $this->repository->find($id);
    }
    
    public function create(array $data)
    {
        return $this->repository->create($data);
    }
    
    public function update(int $id, array $data)
    {
        return $this->repository->update($id, $data);
    }
    
    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }
}
```

### 7. Create Controller

```bash
php artisan make:controller API/ModelController
```

```php
// app/Http/Controllers/API/ModelController.php
class ModelController extends Controller
{
    public function __construct(protected ModelService $service) {}
    
    public function index()
    {
        return response()->json($this->service->getAll());
    }
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $model = $this->service->create($data);
        return response()->json($model, 201);
    }
    
    public function show($id)
    {
        $model = $this->service->getById($id);
        return $model ? response()->json($model) : response()->json(['message' => 'Not Found'], 404);
    }
    
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $updated = $this->service->update($id, $data);
        return $updated ? response()->json(['message' => 'Updated']) : response()->json(['message' => 'Not Found'], 404);
    }
    
    public function destroy($id)
    {
        $deleted = $this->service->delete($id);
        return $deleted ? response()->json(['message' => 'Deleted']) : response()->json(['message' => 'Not Found'], 404);
    }
}
```

### 8. Register Repository Binding

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        \App\Repositories\ModelRepositoryInterface::class,
        \App\Repositories\Impl\ModelRepository::class
    );
}
```

### 9. Add Routes

```php
// routes/api.php
use App\Http\Controllers\API\ModelController;

Route::middleware('api.auth')->group(function () {
    Route::get('/models', [ModelController::class, 'index']);
    Route::get('/models/{id}', [ModelController::class, 'show']);
    
    Route::middleware('role:admin')->group(function () {
        Route::post('/models', [ModelController::class, 'store']);
        Route::put('/models/{id}', [ModelController::class, 'update']);
        Route::delete('/models/{id}', [ModelController::class, 'destroy']);
    });
});
```

### 10. Run Migration

```bash
php artisan migrate
```

## Current API Structure

### Models

- **User** (with roles)
- **Role** (admin, user)
- **Product**
- **Course**
- **Student**
- **Enrollment**

### Endpoints

- **Authentication:** `/api/register`, `/api/login`, `/api/me`, `/api/logout`
- **Products:** `/api/products` (CRUD with role protection)
- **Courses:** `/api/courses` (CRUD with role protection)
- **Students:** `/api/students` (public list, protected CRUD)
- **Enrollments:** `/api/enrollments` (CRUD with role protection)
