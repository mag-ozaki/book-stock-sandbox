<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\StoreUser;
use App\Repositories\StoreRepository;
use App\Services\StoreUserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class StoreUserController extends Controller
{
    public function __construct(
        private StoreUserService $service,
        private StoreRepository $storeRepo,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', StoreUser::class);

        return inertia('Admin/StoreUsers/Index', [
            'storeUsers' => $this->service->listAll(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', StoreUser::class);

        return inertia('Admin/StoreUsers/Create', [
            'stores' => $this->storeRepo->all(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', StoreUser::class);

        $this->service->create($request->validated());

        return redirect()->route('admin.store-users.index')
            ->with('success', 'ユーザーを作成しました。');
    }

    public function edit(StoreUser $storeUser): Response
    {
        $this->authorize('update', $storeUser);

        return inertia('Admin/StoreUsers/Edit', [
            'storeUser' => $storeUser,
            'stores'    => $this->storeRepo->all(),
        ]);
    }

    public function update(StoreUserRequest $request, StoreUser $storeUser): RedirectResponse
    {
        $this->authorize('update', $storeUser);

        $this->service->update($storeUser, $request->validated());

        return redirect()->route('admin.store-users.index')
            ->with('success', 'ユーザー情報を更新しました。');
    }

    public function destroy(StoreUser $storeUser): RedirectResponse
    {
        $this->authorize('delete', $storeUser);

        $this->service->delete($storeUser);

        return redirect()->route('admin.store-users.index')
            ->with('success', 'ユーザーを削除しました。');
    }
}
