<template>
  <Transition name="banner">
    <div v-if="show" class="perfect-day-banner">
      <div class="banner-content">
        <span class="banner-icon">🏆</span>
        <div class="banner-text">
          <span class="banner-title">Perfect Day!</span>
          <span class="banner-subtitle">+{{ bonusXP }} Perfect Day Bonus XP!</span>
        </div>
        <button @click="dismiss" class="banner-close">
          <span>×</span>
        </button>
      </div>
      <div class="banner-particles">
        <span v-for="i in 12" :key="i" class="particle" :style="particleStyle(i)">✨</span>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  bonusXP: {
    type: Number,
    default: 15,
  },
  autoDismiss: {
    type: Boolean,
    default: true,
  },
  autoDismissDelay: {
    type: Number,
    default: 5000,
  },
})

const emit = defineEmits(['dismiss'])
const show = ref(true)

function dismiss() {
  show.value = false
  emit('dismiss')
}

function particleStyle(index) {
  const angle = (index / 12) * 360
  const distance = 60 + Math.random() * 40
  return {
    '--angle': `${angle}deg`,
    '--distance': `${distance}px`,
    '--delay': `${index * 0.1}s`,
  }
}

onMounted(() => {
  if (props.autoDismiss) {
    setTimeout(() => {
      dismiss()
    }, props.autoDismissDelay)
  }
})
</script>

<style scoped>
.perfect-day-banner {
  position: relative;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 50%, #fcd34d 100%);
  border: 2px solid #f59e0b;
  border-radius: 1rem;
  padding: 1rem 1.5rem;
  overflow: hidden;
  box-shadow: 0 10px 25px -5px rgba(251, 191, 36, 0.4);
}

.banner-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  position: relative;
  z-index: 1;
}

.banner-icon {
  font-size: 2.5rem;
  animation: bounce 0.6s ease-in-out infinite alternate;
}

.banner-text {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.banner-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #92400e;
  text-shadow: 0 1px 2px rgba(255, 255, 255, 0.5);
}

.banner-subtitle {
  font-size: 1rem;
  font-weight: 600;
  color: #b45309;
}

.banner-close {
  background: rgba(255, 255, 255, 0.3);
  border: none;
  border-radius: 50%;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1.25rem;
  color: #92400e;
  transition: all 0.2s;
}

.banner-close:hover {
  background: rgba(255, 255, 255, 0.5);
  transform: scale(1.1);
}

.banner-particles {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.particle {
  position: absolute;
  font-size: 1rem;
  opacity: 0;
  animation: particle-burst 1.5s ease-out infinite;
  animation-delay: var(--delay);
}

@keyframes bounce {
  from {
    transform: translateY(0);
  }
  to {
    transform: translateY(-4px);
  }
}

@keyframes particle-burst {
  0% {
    opacity: 1;
    transform: translate(0, 0) scale(0);
  }
  50% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translate(
      calc(cos(var(--angle)) * var(--distance)),
      calc(sin(var(--angle)) * var(--distance))
    ) scale(1);
  }
}

.banner-enter-active {
  animation: slide-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.banner-leave-active {
  animation: slide-out 0.3s ease-in forwards;
}

@keyframes slide-in {
  from {
    opacity: 0;
    transform: translateY(-100%) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes slide-out {
  from {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateY(-100%) scale(0.9);
  }
}
</style>