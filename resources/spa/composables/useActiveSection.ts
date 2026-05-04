import { onMounted, onUnmounted, readonly, ref } from 'vue'

export function useActiveSection(sectionIds: string[]) {
    const activeSection = ref<string | null>(sectionIds[0] ?? null)

    let observer: IntersectionObserver | null = null

    function onScroll() {
        if (window.scrollY < 50) {
            activeSection.value = sectionIds[0] ?? null
        }
    }

    onMounted(() => {
        window.addEventListener('scroll', onScroll, { passive: true })

        observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        activeSection.value = entry.target.id
                        break
                    }
                }
            },
            {
                threshold: 0.3,
                rootMargin: '-20% 0px -60% 0px',
            },
        )

        for (const id of sectionIds) {
            const el = document.getElementById(id)

            if (el) {
                observer.observe(el)
            }
        }
    })

    onUnmounted(() => {
        window.removeEventListener('scroll', onScroll)
        observer?.disconnect()
    })

    return { activeSection: readonly(activeSection) }
}
