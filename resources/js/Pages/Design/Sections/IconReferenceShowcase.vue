<template>
  <Showcase
    title="Icon Reference"
    description="lucide-vue-next only. Group icons by concept so semantics stay consistent across the app."
  >
    <div v-for="group in groups" :key="group.title">
      <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 mb-2">{{ group.title }}</p>
      <div class="grid grid-cols-3 sm:grid-cols-5 lg:grid-cols-8 gap-2">
        <div
          v-for="item in group.items"
          :key="item.name"
          class="flex flex-col items-center gap-1 p-2 rounded-md border border-stone-100 hover:border-stone-300"
        >
          <component :is="item.icon" class="h-5 w-5 text-stone-700" />
          <span class="text-[11px] text-stone-500">{{ item.name }}</span>
        </div>
      </div>
    </div>
  </Showcase>
</template>

<script setup>
import Showcase from '../Components/Showcase.vue'
import {
  DollarSign, CreditCard, Banknote, TrendingUp, TrendingDown,
  Users, User, UserPlus, UserCheck, UserX,
  CheckCircle, XCircle, AlertCircle, Clock, Info,
  Edit, Trash2, Eye, Download, Upload, Plus, Search, Filter,
  ChevronDown, ChevronRight, ArrowLeft, Menu,
  Settings, Bell, Mail, Shield, Activity,
} from 'lucide-vue-next'

const groups = [
  { title: 'Finance', items: [
    { name: 'DollarSign', icon: DollarSign },
    { name: 'CreditCard', icon: CreditCard },
    { name: 'Banknote', icon: Banknote },
    { name: 'TrendingUp', icon: TrendingUp },
    { name: 'TrendingDown', icon: TrendingDown },
  ] },
  { title: 'People', items: [
    { name: 'Users', icon: Users },
    { name: 'User', icon: User },
    { name: 'UserPlus', icon: UserPlus },
    { name: 'UserCheck', icon: UserCheck },
    { name: 'UserX', icon: UserX },
  ] },
  { title: 'Status', items: [
    { name: 'CheckCircle', icon: CheckCircle },
    { name: 'XCircle', icon: XCircle },
    { name: 'AlertCircle', icon: AlertCircle },
    { name: 'Clock', icon: Clock },
    { name: 'Info', icon: Info },
  ] },
  { title: 'Actions', items: [
    { name: 'Edit', icon: Edit },
    { name: 'Trash2', icon: Trash2 },
    { name: 'Eye', icon: Eye },
    { name: 'Download', icon: Download },
    { name: 'Upload', icon: Upload },
    { name: 'Plus', icon: Plus },
    { name: 'Search', icon: Search },
    { name: 'Filter', icon: Filter },
  ] },
  { title: 'Navigation', items: [
    { name: 'ChevronDown', icon: ChevronDown },
    { name: 'ChevronRight', icon: ChevronRight },
    { name: 'ArrowLeft', icon: ArrowLeft },
    { name: 'Menu', icon: Menu },
  ] },
  { title: 'System', items: [
    { name: 'Settings', icon: Settings },
    { name: 'Bell', icon: Bell },
    { name: 'Mail', icon: Mail },
    { name: 'Shield', icon: Shield },
    { name: 'Activity', icon: Activity },
  ] },
]
</script>
