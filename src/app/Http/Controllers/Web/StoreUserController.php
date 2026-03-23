<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreUserRequest;
use App\Models\StoreUser;
use App\Repositories\StoreUserRepository;
use App\Services\StoreUserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class StoreUserController extends Controller
{
    public function __construct(
        private StoreUserService $service,
        private StoreUserRepository $repo,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', StoreUser::class);

        /** @var StoreUser $user */
        $user = auth()->user();

        return inertia('StoreUsers/Index', [
            'storeUsers' => $this->service->listByStore($user->store_id),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', StoreUser::class);

        return inertia('StoreUsers/Create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', StoreUser::class);

        /** @var StoreUser $user */
        $user = auth()->user();

        $this->service->create(array_merge($request->validated(), [
            'store_id' => $user->store_id,
        ]));

        return redirect()->route('store-users.index')
            ->with('success', 'ユーザーを作成しました。');
    }

    public function edit(StoreUser $storeUser): Response
    {
        $this->authorize('update', $storeUser);

        return inertia('StoreUsers/Edit', [
            'storeUser' => $storeUser,
        ]);
    }

    public function update(StoreUserRequest $request, StoreUser $storeUser): RedirectResponse
    {
        $this->authorize('update', $storeUser);

        $this->service->update($storeUser, $request->validated());

        return redirect()->route('store-users.index')
            ->with('success', 'ユーザー情報を更新しました。');
    }

    public function destroy(StoreUser $storeUser): RedirectResponse
    {
        $this->authorize('delete', $storeUser);

        $this->service->delete($storeUser);

        return redirect()->route('store-users.index')
            ->with('success', 'ユーザーを削除しました。');
    }
}
