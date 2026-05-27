<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50">
        <div class="text-center">
            <h1 class="text-6xl font-bold text-blue-600 mb-4">🎮 Magang Quest</h1>
            <p class="text-xl text-gray-600 mb-8">Gamified Internship Logbook System</p>

            <div class="grid grid-cols-3 gap-8 max-w-4xl mx-auto mb-12">
                <div class="quest-card quest-high">
                    <div class="text-4xl mb-2">⚔️</div>
                    <h3 class="font-bold text-lg">Quest System</h3>
                    <p class="text-sm text-gray-600">Assigned, Bounty & Usulan</p>
                </div>
                <div class="quest-card quest-mid">
                    <div class="text-4xl mb-2">🏆</div>
                    <h3 class="font-bold text-lg">Gamification</h3>
                    <p class="text-sm text-gray-600">Points, Streaks & Badges</p>
                </div>
                <div class="quest-card quest-low">
                    <div class="text-4xl mb-2">📊</div>
                    <h3 class="font-bold text-lg">Analytics</h3>
                    <p class="text-sm text-gray-600">Idle Dashboard & Reports</p>
                </div>
            </div>

            <div class="text-sm text-gray-500">
                Laravel {{ $page.props.framework }} + Vue {{ $page.props.vueVersion }}
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    framework: String,
    vueVersion: String,
})
</script>
