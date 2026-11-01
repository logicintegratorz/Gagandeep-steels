<?php
namespace App\Http\Middleware;

use App\Models\{CrmNotification, User};
use Closure;
use Illuminate\Http\Request;

final class RecordCrmNotification
{
    public function handle(Request $request, Closure $next)
    {
        $actor = $request->user();
        $response = $next($request);
        $route = $request->route()->getName();
        $actions = [
            'products.sizes.store' => 'Size added', 'products.sizes.update' => 'Size updated', 'products.sizes.destroy' => 'Size deleted',
            'products.company-prices' => 'Company prices updated',
            'products.store' => 'Product added', 'products.update' => 'Product and price updated',
            'rates.store' => 'Freight rate added', 'rates.update' => 'Freight rate updated', 'rates.destroy' => 'Freight rate deleted',
            'transporters.store' => 'Transporter added', 'transporters.update' => 'Transporter updated', 'transporters.destroy' => 'Transporter deleted',
            'users.store' => 'User created', 'users.update' => 'User account updated', 'users.destroy' => 'User deleted',
            'roles.store' => 'Role created', 'roles.update' => 'Role permissions updated', 'roles.destroy' => 'Role deleted',
            'account.password' => 'Password changed',
        ];
        $flashed = $request->session()->get('_flash.new', []);
        if (!$actor || !isset($actions[$route]) || !$response->isRedirection() || in_array('errors', $flashed) || !array_intersect(['success', 'status'], $flashed)) return $response;
        $message = $request->session()->get('success') ?? $request->session()->get('status');
        if (!$message) return $response;
        if (str_starts_with($route, 'products.sizes.') || $route === 'products.company-prices') {
            $product = $request->route('product');
            $size = $request->route('size');
            $details = array_filter([$product?->name, $request->input('size') ?? $size?->size]);
            if ($details) $message = implode(' · ', $details).': '.$message;
        }
        try {
            $permission = $route === 'account.password' ? null : explode('.', $route)[0].'.view';
            $recipients = User::with('role')->where('active', true)->where(fn ($q) => $q->whereKey($actor->id)->orWhereHas('role', fn ($role) => $role->where('is_admin', true)))->get();
            $rows = [];
            foreach ($recipients as $recipient) {
                // Personal password changes are only visible to the account owner.
                if ($route === 'account.password' && $recipient->id !== $actor->id) continue;
                if ($permission && !$recipient->allows($permission)) continue;
                $rows[$recipient->id] = $this->row($recipient->id, $actions[$route], $actor->name.': '.$message, $permission);
            }
            if ($route === 'users.store' && ($newUser = User::where('email', $request->input('email'))->first())) {
                $rows[$newUser->id] = $this->row($newUser->id, 'Welcome to Gagandeep Steels', 'Your CRM account is ready. You can change your password in My account. Contact your Administrator if you need different access.', null);
            }
            if ($route === 'users.update' && ($target = $request->route('user')) && $target->id !== $actor->id && User::whereKey($target->id)->exists()) {
                $rows[$target->id] = $this->row($target->id, 'Your account was updated', $request->filled('password') ? 'An Administrator reset your password. Contact your Administrator for your new password.' : 'Your account details or access were updated. Sign in again to use your current permissions.', null);
            }
            if ($route === 'roles.update' && ($role = $request->route('role'))) {
                foreach (User::where('role_id', $role->id)->where('active', true)->get() as $member) {
                    $rows[$member->id] = $this->row($member->id, 'Your permissions changed', 'Your role permissions were updated. Sign in again to use your current access.', null);
                }
            }
            if ($rows) CrmNotification::insert(array_values($rows));
        } catch (\Throwable $exception) {
            // A notification failure must not report a successfully saved business record as failed.
            report($exception);
        }
        return $response;
    }

    private function row(int $userId, string $title, string $message, ?string $permission): array
    {
        return ['user_id' => $userId, 'title' => $title, 'message' => $message, 'permission' => $permission, 'read_at' => null, 'created_at' => now(), 'updated_at' => now()];
    }
}
