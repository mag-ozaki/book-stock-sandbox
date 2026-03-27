<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, useForm, router } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  store: Object,
  apiKeys: Array,
  newlyIssuedKey: {
    type: String,
    default: null,
  },
})

const form = useForm({
  name: '',
  allowed_ips: '',
  expires_at: '',
})

const submit = () => {
  const ips = form.allowed_ips
    .split('\n')
    .map(s => s.trim())
    .filter(s => s.length > 0)
  form.transform(data => ({
    ...data,
    allowed_ips: ips.length > 0 ? ips : null,
    expires_at: data.expires_at || null,
  })).post(route('admin.stores.api-keys.store', props.store.id), {
    preserveScroll: true,
  })
}

const copyKey = () => {
  navigator.clipboard.writeText(props.newlyIssuedKey)
}

const toggle = (apiKey) => {
  router.patch(route('admin.stores.api-keys.update', [props.store.id, apiKey.id]), {
    is_active: !apiKey.is_active,
  })
}

const destroy = (apiKey) => {
  if (confirm('このAPIキーを削除しますか？')) {
    router.delete(route('admin.stores.api-keys.destroy', [props.store.id, apiKey.id]))
  }
}
</script>

<template>
  <div>
    <div class="flex items-center gap-3 mb-6">
      <Link :href="route('admin.stores.index')" class="text-indigo-600 hover:underline text-sm">← 店舗一覧に戻る</Link>
      <h1 class="text-2xl font-bold text-gray-800">{{ store.name }} の APIキー管理</h1>
    </div>

    <!-- 新規発行キーのワンタイム表示 -->
    <div v-if="newlyIssuedKey" class="bg-yellow-50 border border-yellow-400 rounded p-4 mb-6">
      <p class="text-sm font-medium text-yellow-800 mb-2">
        以下のAPIキーは一度しか表示されません。安全な場所にコピーしてください。
      </p>
      <div class="flex items-center gap-3">
        <span class="font-mono break-all text-sm text-yellow-900 flex-1">{{ newlyIssuedKey }}</span>
        <button
          @click="copyKey"
          class="shrink-0 bg-yellow-400 text-yellow-900 px-3 py-1 rounded text-sm hover:bg-yellow-500"
        >
          コピー
        </button>
      </div>
    </div>

    <!-- 新規発行フォーム -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-700 mb-4">新規APIキー発行</h2>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            識別名 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
            :class="{ 'border-red-400': form.errors.name }"
          />
          <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">許可IPアドレス</label>
          <textarea
            v-model="form.allowed_ips"
            rows="3"
            :placeholder="'192.168.1.1\n10.0.0.1'"
            class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400 font-mono text-sm"
            :class="{ 'border-red-400': form.errors.allowed_ips }"
          ></textarea>
          <p class="text-gray-400 text-xs mt-1">1行1IPアドレス。空欄の場合はIP制限なし。</p>
          <p v-if="form.errors.allowed_ips" class="text-red-500 text-sm mt-1">{{ form.errors.allowed_ips }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">有効期限</label>
          <input
            v-model="form.expires_at"
            type="date"
            class="border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
            :class="{ 'border-red-400': form.errors.expires_at }"
          />
          <p class="text-gray-400 text-xs mt-1">空欄の場合は無期限。</p>
          <p v-if="form.errors.expires_at" class="text-red-500 text-sm mt-1">{{ form.errors.expires_at }}</p>
        </div>

        <div class="pt-2">
          <button
            type="submit"
            :disabled="form.processing"
            class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 disabled:opacity-50 text-sm"
          >
            APIキーを発行
          </button>
        </div>
      </form>
    </div>

    <!-- APIキー一覧テーブル -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">識別名</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">状態</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">最終使用</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">有効期限</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">発行日時</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">許可IP</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="apiKey in apiKeys" :key="apiKey.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ apiKey.name }}</td>
            <td class="px-6 py-4 text-sm">
              <span
                v-if="apiKey.is_active"
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
              >
                有効
              </span>
              <span
                v-else
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
              >
                無効
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ apiKey.last_used_at ?? '未使用' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ apiKey.expires_at ?? '無期限' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ apiKey.created_at }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ apiKey.allowed_ips ? apiKey.allowed_ips.join(', ') : '制限なし' }}
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-3">
              <button
                @click="toggle(apiKey)"
                class="hover:underline"
                :class="apiKey.is_active ? 'text-yellow-600' : 'text-green-600'"
              >
                {{ apiKey.is_active ? '無効化' : '有効化' }}
              </button>
              <button @click="destroy(apiKey)" class="text-red-500 hover:underline">削除</button>
            </td>
          </tr>
          <tr v-if="apiKeys.length === 0">
            <td colspan="7" class="px-6 py-8 text-center text-gray-400">APIキーが登録されていません</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
