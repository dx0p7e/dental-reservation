import { onMounted, onUnmounted, readonly, ref } from 'vue'

export function useScrolled(threshold: number) {
    const scrolled = ref(false)

    function onScroll() {
        scrolled.value = window.scrollY >= threshold
    }

    onMounted(() => {
        onScroll()
        window.addEventListener('scroll', onScroll, { passive: true })
    })

    onUnmounted(() => {
        window.removeEventListener('scroll', onScroll)
    })

    return { scrolled: readonly(scrolled) }
}
