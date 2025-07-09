<template>
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
          <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold mb-2 text-forest-dark">
              Dashboard Overview
            </h1>
            <p class="text-gray-600 text-sm sm:text-base">Welcome back! Here's what's happening with your business today.</p>
          </div>

          <!-- Stats Cards -->
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
              </TabsContent>
            </Tabs>
          </div>
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
