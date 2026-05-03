<template>
  <!--
  ============================================================
  DESIGN SYSTEM REFERENCE — resources/js/Pages/Design/Design01.vue
  Preview at: /design/design-01

  This file is the single source of truth for UI patterns.
  All new pages should import components from ./Components/
  and follow the patterns demonstrated here.

  SECTIONS IN THIS FILE:
    ✓  Layout (Sidebar + Header)
    ✓  StatsCard (4 variants with trends)
    ✓  Tabs (TabsList / TabsTrigger / TabsContent)
    ✓  Card / CardHeader / CardContent / CardTitle
    ✓  Progress bar
    ✓  Badge (secondary, outline, default) — incomplete, see @TODO
    ✓  Button (outline/sm) — incomplete, see @TODO
    ✓  UsersTable
    ✓  ActivityTimeline
    ✓  AlertsSection

  @TODO — MISSING SECTIONS (complete before Phase 4 frontend work):

  CRITICAL (needed for admin + agent forms):
  [ ] Form Elements — Input, Textarea, Select, Checkbox, Radio
      → These need new shadcn-based components in ./Components/
      → Show: label + input + error state + disabled state
  [ ] All Button variants — default (stone-900), secondary (amber-600),
      destructive (red-600), ghost, link; all 4 sizes (default, sm, lg, icon)
  [ ] All Badge variants — add: success (green), warning (yellow), destructive (red)
  [ ] ConfirmationModal pattern — used for approve/reject/downgrade flows
      → Show: title, body text, cancel + confirm buttons, destructive variant
  [ ] Status badge system — show the custom CSS classes from app.css:
      status-pending (yellow), status-paid (green), status-cancelled (red)
      + active/inactive/suspended/expired/rejected variants

  HIGH (needed for most pages):
  [ ] Breadcrumb navigation pattern — show: Home > Section > Current Page
  [ ] Page layout template — show the standard admin page structure:
      breadcrumb + title row (with action button) + content area
  [ ] EmptyState component — icon + headline + subtext + optional CTA
      → Needed for every list screen when no records exist
  [ ] Pagination component — prev/next + page numbers + "showing X of Y"
  [ ] Toast / flash notification — Inertia $page.props.flash.success / error
  [ ] All Alert variants — Alert.vue exists but is not shown here
      → Show: default, destructive, success, warning

  MEDIUM (needed for specific screens):
  [ ] File upload input pattern — choose file + preview name + remove
  [ ] Status stepper / progress timeline — for payout lifecycle:
      Pending → Approved → Processing → Paid (with dates)
  [ ] BarChart, LineChart, PieChart — components exist but not shown
  [ ] Inbox notification row — subject, body excerpt, timestamp, unread badge
  [ ] Multi-step wizard header — step dots 1-6 with active/completed states

  LOW (polish / reference):
  [ ] Color palette showcase — visual swatches for all brand + accent colors
  [ ] Typography scale — h1 through h4, body, muted, label sizes in context
  [ ] Icon reference — show key Lucide icons by concept:
      (DollarSign=money, Users=team, ShoppingCart=sales, etc.)
  [ ] Loading / skeleton state — show shimmer placeholder for cards + tables
  [ ] Date range picker pattern — from/to inputs with calendar
  ============================================================
  -->

  <div class="min-h-screen h-screen bg-cream font-sans">
    <!-- Google Fonts Import -->
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <div class="flex flex-col lg:flex-row h-full">
      <!-- Sidebar -->
      <Sidebar :isOpen="sidebarOpen" @toggle="toggleSidebar" />

      <div class="flex-1 flex flex-col lg:ml-0 min-h-0">
        <!-- Header -->
        <Header @toggle="toggleSidebar" />

        <!-- Main Content -->
        <main class="flex-1 p-4 sm:p-6 overflow-y-auto">

          <!-- @TODO: Add breadcrumb pattern here -->
          <!-- Example: Home > Admin > Dashboard -->

          <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold mb-2 text-forest-dark">
              Dashboard Overview
            </h1>
            <p class="text-gray-600 text-sm sm:text-base">Welcome back! Here's what's happening with your business today.</p>
          </div>

          <!-- @TODO: Add page layout template block here showing:
               breadcrumb + title + description + right-side action button -->


          <!-- ── StatsCard ───────────────────────────────────────── -->
          <!-- @TODO: StatsCard icon prop only accepts: DollarSign, Users, ShoppingCart, Activity
               Add more icons to StatsCard.vue iconComponent map as needed -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
            <StatsCard
              title="Total Revenue"
              value="$54,239"
              change="+12.5% from last month"
              icon="DollarSign"
              trend="up"
              :progress="75"
            />
            <StatsCard
              title="Active Users"
              value="2,847"
              change="+8.2% from last week"
              icon="Users"
              trend="up"
              :progress="82"
            />
            <StatsCard
              title="Total Orders"
              value="1,423"
              change="+15.3% from yesterday"
              icon="ShoppingCart"
              trend="up"
              :progress="65"
            />
            <StatsCard
              title="Conversion Rate"
              value="3.24%"
              change="-2.1% from last month"
              icon="Activity"
              trend="down"
              :progress="32"
            />
          </div>

          <!-- @TODO: Add Button variants section here
               Show all 6 variants × 4 sizes in a grid:
               default (stone-900), secondary (amber-600), destructive (red),
               outline, ghost, link — sizes: default, sm, lg, icon -->

          <!-- @TODO: Add Badge variants section here
               Show: default, secondary, outline, success (green), warning (yellow), destructive (red)
               Also show status badge classes: status-pending, status-paid, status-cancelled -->

          <!-- @TODO: Add Alert variants section here (uses Alert.vue)
               Show: default, destructive, success, warning -->

          <!-- @TODO: Add form elements section here — these need new shadcn components:
               Input (text, with label + error state)
               Textarea
               Select / Dropdown
               Checkbox
               Radio button group
               File upload input (choose file + filename preview) -->

          <!-- @TODO: Add ConfirmationModal pattern here
               Show: trigger button → modal with title, body, cancel + confirm -->

          <!-- @TODO: Add EmptyState pattern here
               Show: icon + headline + subtext + optional CTA button -->

          <!-- @TODO: Add status stepper / timeline here
               Show: Pending → Approved → Processing → Paid lifecycle stepper -->

          <!-- Tabs Section -->
          <div class="mb-6 sm:mb-8">
            <Tabs :model-value="activeTab" @update:model-value="activeTab = $event" class="w-full">
              <TabsList class="mb-4 sm:mb-6 flex-wrap">
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="analytics">Analytics</TabsTrigger>
                <TabsTrigger value="reports">Reports</TabsTrigger>
                <TabsTrigger value="notifications">Notifications</TabsTrigger>
              </TabsList>

              <TabsContent value="overview" class="space-y-4 sm:space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                  <div class="lg:col-span-2">
                    <UsersTable />
                  </div>
                  <div>
                    <ActivityTimeline />
                  </div>
                </div>
              </TabsContent>

              <TabsContent value="analytics" class="space-y-4 sm:space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                  <Card>
                    <CardHeader>
                      <CardTitle>Performance Metrics</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div class="space-y-4">
                        <div>
                          <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium">Page Views</span>
                            <span class="text-sm text-gray-500">78%</span>
                          </div>
                          <Progress :value="78" />
                        </div>
                        <div>
                          <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium">Bounce Rate</span>
                            <span class="text-sm text-gray-500">23%</span>
                          </div>
                          <Progress :value="23" />
                        </div>
                        <div>
                          <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium">Session Duration</span>
                            <span class="text-sm text-gray-500">65%</span>
                          </div>
                          <Progress :value="65" />
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader>
                      <CardTitle>Traffic Sources</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div class="space-y-3">
                        <div class="flex items-center justify-between">
                          <span class="text-sm">Direct Traffic</span>
                          <Badge variant="secondary">42%</Badge>
                        </div>
                        <div class="flex items-center justify-between">
                          <span class="text-sm">Social Media</span>
                          <Badge variant="outline">28%</Badge>
                        </div>
                        <div class="flex items-center justify-between">
                          <span class="text-sm">Search Engines</span>
                          <Badge>30%</Badge>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>
              </TabsContent>

              <TabsContent value="reports">
                <Card>
                  <CardHeader>
                    <CardTitle>Generated Reports</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div class="space-y-4">
                      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 border rounded-lg gap-2 sm:gap-0">
                        <div>
                          <h4 class="font-medium">Monthly Sales Report</h4>
                          <p class="text-sm text-gray-500">Generated 2 hours ago</p>
                        </div>
                        <Button variant="outline" size="sm">
                          <Download class="h-4 w-4 mr-2" />
                          Download
                        </Button>
                      </div>
                      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 border rounded-lg gap-2 sm:gap-0">
                        <div>
                          <h4 class="font-medium">User Analytics Report</h4>
                          <p class="text-sm text-gray-500">Generated yesterday</p>
                        </div>
                        <Button variant="outline" size="sm">
                          <Download class="h-4 w-4 mr-2" />
                          Download
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="notifications">
                <AlertsSection />
                <!-- @TODO: Add inbox notification row pattern here
                     Show: unread dot, subject, body excerpt, timestamp, [Mark Read] button -->
              </TabsContent>
            </Tabs>
          </div>

          <!-- @TODO: Add charts section here — BarChart, LineChart, PieChart are in ./Components/
               Show each with sample data and labels -->

          <!-- @TODO: Add pagination component here (component does not exist yet — create it)
               Show: prev button + page numbers + next button + "Showing X–Y of Z" -->

          <!-- @TODO: Add color palette showcase section
               Swatches: forest-dark, forest-light, gold, cream, accent-red/orange/green/blue/gray
               Stone neutrals: stone-50 through stone-900 -->

          <!-- @TODO: Add typography scale section
               h1 (text-3xl font-bold text-forest-dark)
               h2 (text-2xl font-semibold text-forest-dark)
               h3 (text-xl font-semibold text-stone-800)
               Body (text-sm text-stone-600)
               Muted (text-sm text-stone-400)
               Label (text-sm font-medium text-stone-700) -->

          <!-- @TODO: Add icon reference section
               Show key Lucide icons by concept grouped:
               Finance: DollarSign, CreditCard, Banknote, TrendingUp, TrendingDown
               People: Users, User, UserPlus, UserCheck, UserX
               Status: CheckCircle, XCircle, AlertCircle, Clock, Info
               Actions: Edit, Trash2, Eye, Download, Upload, Plus, Search, Filter
               Navigation: ChevronDown, ChevronRight, ArrowLeft, Menu
               System: Settings, Bell, Mail, Shield, Activity -->

          <!-- @TODO: Add loading/skeleton state section
               Show shimmer placeholder card and table row skeletons -->

        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import {
  Menu,
  X,
  Home,
  Users,
  Settings,
  BarChart3,
  FileText,
  Bell,
  Search,
  User,
  ChevronDown,
  TrendingUp,
  UserPlus,
  Activity,
  Calendar,
  DollarSign,
  ShoppingCart,
  Eye,
  MoreHorizontal,
  Filter,
  Download,
  Plus,
  AlertCircle,
  CheckCircle,
  XCircle,
  Clock
} from 'lucide-vue-next'

// Components
import Sidebar from './Components/Sidebar.vue'
import Header from './Components/Header.vue'
import StatsCard from './Components/StatsCard.vue'
import UsersTable from './Components/UsersTable.vue'
import ActivityTimeline from './Components/ActivityTimeline.vue'
import AlertsSection from './Components/AlertsSection.vue'
import Tabs from './Components/Tabs.vue'
import TabsList from './Components/TabsList.vue'
import TabsTrigger from './Components/TabsTrigger.vue'
import TabsContent from './Components/TabsContent.vue'
import Card from './Components/Card.vue'
import CardHeader from './Components/CardHeader.vue'
import CardContent from './Components/CardContent.vue'
import CardTitle from './Components/CardTitle.vue'
import Progress from './Components/Progress.vue'
import Badge from './Components/Badge.vue'
import Button from './Components/Button.vue'

// Reactive data
const sidebarOpen = ref(false)
const activeTab = ref('overview')

// Methods
const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<style scoped>
/* Custom styles for Geist font */
@import url('https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&display=swap');

.font-sans {
  font-family: 'Geist', sans-serif !important;
}
</style>
