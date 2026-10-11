<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/Admin/AdminLayout.vue';
import AdminStatsGrid from '@/Components/Admin/AdminStatsGrid.vue';
import AdminTabsNavigation from '@/Components/Admin/AdminTabsNavigation.vue';
import AdminOrdersTab from '@/Components/Admin/AdminOrdersTab.vue';
import AdminNfcTab from '@/Components/Admin/AdminNfcTab.vue';
import AdminUsersTab from '@/Components/Admin/AdminUsersTab.vue';
import AdminLogsTab from '@/Components/Admin/AdminLogsTab.vue';
import AdminPortfolioTab from '@/Components/Admin/AdminPortfolioTab.vue';

// Isolated Modals
import ConnectOrderModal from '@/Components/Admin/Modals/ConnectOrderModal.vue';
import OfflineOrderModal from '@/Components/Admin/Modals/OfflineOrderModal.vue';
import CreateCardModal from '@/Components/Admin/Modals/CreateCardModal.vue';
import BatchCardModal from '@/Components/Admin/Modals/BatchCardModal.vue';
import AssignCardModal from '@/Components/Admin/Modals/AssignCardModal.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    users: {
        type: Object,
        required: true,
    },
    allUsers: {
        type: Array,
        default: () => [],
    },
    nfcDevices: {
        type: Array,
        default: () => [],
    },
    nfcOrders: {
        type: Array,
        default: () => [],
    },
    unassignedCards: {
        type: Array,
        default: () => [],
    },
    auditLogs: {
        type: Array,
        default: () => [],
    },
    portfolioPlans: {
        type: Array,
        default: () => [],
    },
    portfolioVouchers: {
        type: Array,
        default: () => [],
    },
    portfolioPaymentMethods: {
        type: Array,
        default: () => [],
    },
    portfolioOrders: {
        type: Array,
        default: () => [],
    },
    portfolioEntitlements: {
        type: Array,
        default: () => [],
    },
    userPortfolios: {
        type: Array,
        default: () => [],
    },
    portfolioSettings: {
        type: Object,
        default: () => ({}),
    },
});

// Active Tab ('orders', 'nfc', 'users', 'logs')
const currentTab = ref('orders');

// Modal States
const showCreateModal = ref(false);
const showBatchModal = ref(false);
const showAssignModal = ref(false);
const showConnectOrderModal = ref(false);
const showOfflineOrderModal = ref(false);

const activeEditingCard = ref(null);
const activeConnectingOrder = ref(null);

function handleOpenConnectModal(order) {
    activeConnectingOrder.value = order;
    showConnectOrderModal.value = true;
}

function handleOpenAssignModal(card) {
    activeEditingCard.value = card;
    showAssignModal.value = true;
}
</script>

<template>
    <AdminLayout
        title="Super Admin · Manajemen Platform & RFID LUNOU"
        :active-tab="currentTab"
        @change-tab="currentTab = $event"
    >
        <!-- Real-time Multi-Device Stats Overview -->
        <AdminStatsGrid :stats="stats" />

        <!-- Responsive Tab Switcher -->
        <AdminTabsNavigation
            v-model:currentTab="currentTab"
            :orders-count="nfcOrders.length"
            :nfc-count="nfcDevices.length"
            :users-count="users.total || users.data?.length || 0"
            :pending-portfolio-orders-count="stats.pending_portfolio_orders || 0"
        />

        <!-- TAB 0: Orders & Pengajuan -->
        <AdminOrdersTab
            v-if="currentTab === 'orders'"
            :orders="nfcOrders"
            :unassigned-cards="unassignedCards"
            @open-offline-modal="showOfflineOrderModal = true"
            @open-connect-modal="handleOpenConnectModal"
            @switch-tab="currentTab = $event"
        />

        <!-- TAB PORTFOLIO MANAGEMENT -->
        <AdminPortfolioTab
            v-else-if="currentTab === 'portfolio'"
            :stats="stats"
            :portfolio-plans="portfolioPlans"
            :portfolio-vouchers="portfolioVouchers"
            :portfolio-payment-methods="portfolioPaymentMethods"
            :portfolio-orders="portfolioOrders"
            :portfolio-entitlements="portfolioEntitlements"
            :user-portfolios="userPortfolios"
            :portfolio-settings="portfolioSettings"
            :all-users="allUsers"
        />

        <!-- TAB 1: RFID/NFC Hardware Catalog -->
        <AdminNfcTab
            v-else-if="currentTab === 'nfc'"
            :nfc-devices="nfcDevices"
            :unassigned-cards="unassignedCards"
            @open-create-modal="showCreateModal = true"
            @open-batch-modal="showBatchModal = true"
            @open-assign-modal="handleOpenAssignModal"
        />

        <!-- TAB 2: User Accounts Moderation -->
        <AdminUsersTab
            v-else-if="currentTab === 'users'"
            :users="users"
        />

        <!-- TAB 3: Audit Security Logs -->
        <AdminLogsTab
            v-else-if="currentTab === 'logs'"
            :audit-logs="auditLogs"
        />

        <!-- MODALS (Isolated Components) -->
        <ConnectOrderModal
            :show="showConnectOrderModal"
            :order="activeConnectingOrder"
            :unassigned-cards="unassignedCards"
            @close="showConnectOrderModal = false; activeConnectingOrder = null;"
        />

        <OfflineOrderModal
            :show="showOfflineOrderModal"
            @close="showOfflineOrderModal = false"
        />

        <CreateCardModal
            :show="showCreateModal"
            :all-users="allUsers"
            @close="showCreateModal = false"
        />

        <BatchCardModal
            :show="showBatchModal"
            @close="showBatchModal = false"
        />

        <AssignCardModal
            :show="showAssignModal"
            :card="activeEditingCard"
            :all-users="allUsers"
            @close="showAssignModal = false; activeEditingCard = null;"
        />
    </AdminLayout>
</template>
