import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import { useScrolled } from '../useScrolled'

describe('useScrolled', () => {
    let addSpy: ReturnType<typeof vi.spyOn>
    let removeSpy: ReturnType<typeof vi.spyOn>
    let scrollHandler: (() => void) | null = null

    beforeEach(() => {
        Object.defineProperty(window, 'scrollY', { value: 0, writable: true, configurable: true })

        addSpy = vi.spyOn(window, 'addEventListener').mockImplementation((event, handler) => {
            if (event === 'scroll') {
                scrollHandler = handler as () => void
            }
        })

        removeSpy = vi.spyOn(window, 'removeEventListener').mockImplementation(() => {})
    })

    afterEach(() => {
        vi.restoreAllMocks()
        scrollHandler = null
    })

    it('returns scrolled=false at page top', async () => {
        Object.defineProperty(window, 'scrollY', { value: 0, configurable: true })

        const app = document.createElement('div')
        document.body.appendChild(app)

        const { mount } = await import('@vue/test-utils')
        const { defineComponent } = await import('vue')

        const TestComponent = defineComponent({
            setup() {
                const { scrolled } = useScrolled(80)
                return { scrolled }
            },
            template: '<div>{{ scrolled }}</div>',
        })

        const wrapper = mount(TestComponent)
        expect(wrapper.text()).toBe('false')
        wrapper.unmount()
        document.body.removeChild(app)
    })

    it('returns scrolled=true after threshold', async () => {
        const { mount } = await import('@vue/test-utils')
        const { defineComponent, nextTick } = await import('vue')

        const TestComponent = defineComponent({
            setup() {
                const { scrolled } = useScrolled(80)
                return { scrolled }
            },
            template: '<div>{{ scrolled }}</div>',
        })

        const wrapper = mount(TestComponent)

        Object.defineProperty(window, 'scrollY', { value: 100, configurable: true })
        scrollHandler?.()
        await nextTick()

        expect(wrapper.text()).toBe('true')
        wrapper.unmount()
    })

    it('removes the scroll listener on unmount', async () => {
        const { mount } = await import('@vue/test-utils')
        const { defineComponent } = await import('vue')

        const TestComponent = defineComponent({
            setup() {
                useScrolled(80)
            },
            template: '<div />',
        })

        const wrapper = mount(TestComponent)
        wrapper.unmount()

        expect(removeSpy).toHaveBeenCalledWith('scroll', expect.any(Function))
    })
})
