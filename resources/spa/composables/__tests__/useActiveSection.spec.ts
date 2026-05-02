import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useActiveSection } from '../useActiveSection'

describe('useActiveSection', () => {
    let observerCallback: IntersectionObserverCallback | null = null
    let observedTargets: Element[] = []

    beforeEach(() => {
        observedTargets = []

        class MockObserver {
            constructor(callback: IntersectionObserverCallback) {
                observerCallback = callback
            }
            observe(el: Element) { observedTargets.push(el) }
            disconnect() {}
        }

        vi.stubGlobal('IntersectionObserver', MockObserver)
    })

    afterEach(() => {
        vi.restoreAllMocks()
        vi.unstubAllGlobals()
        observerCallback = null
        observedTargets = []
    })

    it('returns activeSection=null initially', async () => {
        const { mount } = await import('@vue/test-utils')
        const { defineComponent } = await import('vue')

        const TestComponent = defineComponent({
            setup() {
                const { activeSection } = useActiveSection(['hero', 'services'])
                return { activeSection }
            },
            template: '<div>{{ activeSection ?? "null" }}</div>',
        })

        const wrapper = mount(TestComponent)
        expect(wrapper.text()).toBe('null')
        wrapper.unmount()
    })

    it('updates activeSection when IntersectionObserver fires', async () => {
        const { mount } = await import('@vue/test-utils')
        const { defineComponent, nextTick } = await import('vue')

        const section = document.createElement('section')
        section.id = 'services'
        document.body.appendChild(section)

        const TestComponent = defineComponent({
            setup() {
                const { activeSection } = useActiveSection(['hero', 'services'])
                return { activeSection }
            },
            template: '<div>{{ activeSection ?? "null" }}</div>',
        })

        const wrapper = mount(TestComponent)

        // Simulate IntersectionObserver firing for services section
        observerCallback?.(
            [{ isIntersecting: true, target: section } as unknown as IntersectionObserverEntry],
            {} as IntersectionObserver,
        )
        await nextTick()

        expect(wrapper.text()).toBe('services')

        wrapper.unmount()
        document.body.removeChild(section)
    })
})
