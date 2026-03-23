<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ storeUser: Object, stores: Array })

const form = useForm({
  store_id: props.storeUser.store_id,
  name: props.storeUser.name,
  email: props.storeUser.email,
  password: '',
  password_confirmation: '',
  role: props.storeUser.role,
})

const submit = () => form.put(route('admin.store-users.update', props.storeUser.id))
</script>

<template>
  <div class="max-w-xl">
    <div class="flex items-center gap-3 mb-6">
      <Link :href="route('admin.store-users.index')" class="text-indigo-600 hover:underline text-sm">← 一覧に戻る</Link>
      <h1 class="text-2xl font-bold text-gray-800">ユーザーの編集</h1>
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-xl shadow p-6 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">店舗 <span class="text-red-500">*</span></label>
        <select v-model="form.store_id"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
          :class="{ 'border-red-400': form.errors.store_id }">
          <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <p v-if="form.errors.store_id" class="text-red-500 text-sm mt-1">{{ form.errors.store_id }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">名前 <span class="text-red-500">*</span></label>
        <input v-model="form.name" type="text"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
          :class="{ 'border-red-400': form.errors.name }" />
        <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">メールアドレス <span class="text-red-500">*</span></label>
        <input v-model="form.email" type="email"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
          :class="{ 'border-red-400': form.errors.email }" />
        <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          パスワード <span class="text-gray-400 text-xs font-normal">（変更しない場合は空欄）</span>
        </label>
        <input v-model="form.password" type="password"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
          :class="{ 'border-red-400': form.errors.password }" />
        <p v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">パスワード（確認）</label>
        <input v-model="form.password_confirmation" type="password"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">ロール</label>
        <select v-model="form.role"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
          <option value="owner">オーナー</option>
          <option value="employee">従業員</option>
        </select>
      </div>

      <div class="pt-2">
        <button type="submit" :disabled="form.processing"
          class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 disabled:opacity-50">
          更新する
        </button>
      </div>
    </form>
  </div>
</template>
