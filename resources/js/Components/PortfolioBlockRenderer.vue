<script setup>
import LunouIcon from '@/Components/LunouIcon.vue';

defineProps({
    blocks: {
        type: Array,
        default: () => [],
    },
    themeStyle: {
        type: Object,
        default: () => ({
            card: 'bg-white border border-zinc-200 text-zinc-900',
            textMuted: 'text-zinc-500',
            accentText: 'text-blue-600',
            accentBg: 'bg-zinc-950 text-white',
        }),
    },
});
</script>

<template>
    <div v-if="blocks && blocks.length" class="space-y-6 w-full">
        <template v-for="(block, idx) in blocks" :key="block.id || idx">
            <!-- 1. HEADING BLOCK -->
            <h2
                v-if="block.type === 'heading'"
                class="font-black tracking-tight leading-tight text-zinc-950"
                :class="[
                    block.level === 'h1' ? 'text-2xl sm:text-4xl md:text-5xl' :
                    block.level === 'h2' ? 'text-xl sm:text-3xl md:text-4xl' :
                    block.level === 'h3' ? 'text-lg sm:text-2xl' : 'text-base sm:text-xl',
                    block.align === 'center' ? 'text-center' :
                    block.align === 'right' ? 'text-right' : 'text-left'
                ]"
            >
                {{ block.text }}
            </h2>

            <!-- 1B. SUB-JUDUL / SEKSI BARU (cth: PRESTASI & PENGHARGAAN) -->
            <div
                v-else-if="block.type === 'subheading' || block.type === 'section_header'"
                class="space-y-2 py-3 w-full"
                :class="[
                    block.align === 'center' ? 'text-center mx-auto' :
                    block.align === 'right' ? 'text-right ml-auto' : 'text-left'
                ]"
            >
                <div v-if="block.tag" class="flex" :class="[
                    block.align === 'center' ? 'justify-center' :
                    block.align === 'right' ? 'justify-end' : 'justify-start'
                ]">
                    <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-blue-50 text-blue-700 border border-blue-200/80 shadow-2xs">
                        {{ block.tag }}
                    </span>
                </div>
                <h3 class="text-2xl sm:text-3xl md:text-4xl font-black tracking-tight leading-tight text-zinc-950">
                    {{ block.title || block.text }}
                </h3>
                <p v-if="block.subtitle || block.description" class="text-sm sm:text-base max-w-3xl leading-relaxed" :class="[
                    block.align === 'center' ? 'mx-auto' : '',
                    themeStyle.textMuted
                ]">
                    {{ block.subtitle || block.description }}
                </p>
            </div>

            <!-- 2. PARAGRAPH / TEXT BLOCK -->
            <div
                v-else-if="block.type === 'text'"
                class="leading-relaxed"
                :class="[
                    block.size === 'sm' ? 'text-xs sm:text-sm' :
                    block.size === 'lg' ? 'text-base sm:text-lg' : 'text-sm sm:text-base',
                    block.align === 'center' ? 'text-center mx-auto' :
                    block.align === 'right' ? 'text-right ml-auto' : 'text-left',
                    themeStyle.textMuted
                ]"
            >
                <p class="whitespace-pre-line">{{ block.text }}</p>
            </div>

            <!-- 2B. PHOTO CARD (FOTO + JUDUL PRESTASI + KETERANGAN DI BAWAHNYA) -->
            <div
                v-else-if="block.type === 'photo_card'"
                class="flex flex-col space-y-3.5 w-full"
                :class="[
                    block.align === 'center' ? 'items-center text-center mx-auto' :
                    block.align === 'right' ? 'items-end text-right ml-auto' : 'items-start text-left',
                    block.width === 'medium' ? 'max-w-md' :
                    block.width === 'large' ? 'max-w-2xl' : 'w-full'
                ]"
            >
                <div
                    class="w-full overflow-hidden rounded-2xl sm:rounded-3xl border border-zinc-200/80 shadow-xs bg-zinc-100 relative group"
                    :class="[
                        block.aspect_ratio === 'square' ? 'aspect-square' :
                        block.aspect_ratio === 'wide' ? 'aspect-[21/9]' :
                        block.aspect_ratio === 'auto' ? 'max-h-[500px]' : 'aspect-video'
                    ]"
                >
                    <img
                        :src="block.url"
                        :alt="block.title || 'Foto'"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                        loading="lazy"
                    />
                </div>

                <!-- Judul & Keterangan persis di bawah foto -->
                <div class="space-y-1.5 w-full">
                    <div v-if="block.meta" class="flex" :class="[
                        block.align === 'center' ? 'justify-center' :
                        block.align === 'right' ? 'justify-end' : 'justify-start'
                    ]">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-md">
                            {{ block.meta }}
                        </span>
                    </div>
                    <h4 v-if="block.title" class="text-base sm:text-xl font-extrabold tracking-tight text-zinc-950">
                        {{ block.title }}
                    </h4>
                    <p v-if="block.description" class="text-xs sm:text-sm leading-relaxed" :class="themeStyle.textMuted">
                        {{ block.description }}
                    </p>
                </div>
            </div>

            <!-- 2C. PHOTO GRID (GALERI FOTO PRESTASI BERJEJER) -->
            <div
                v-else-if="block.type === 'photo_grid'"
                class="grid grid-cols-1 sm:grid-cols-2 gap-6 w-full"
            >
                <div
                    v-for="(item, pIdx) in (block.items || [])"
                    :key="pIdx"
                    class="space-y-3 flex flex-col group"
                >
                    <div class="aspect-video w-full rounded-2xl overflow-hidden border border-zinc-200/80 shadow-2xs bg-zinc-100">
                        <img
                            :src="item.url"
                            :alt="item.title || 'Foto Galeri'"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            loading="lazy"
                        />
                    </div>
                    <div class="space-y-1">
                        <span v-if="item.meta" class="text-[10px] font-bold uppercase tracking-wider text-blue-600 block">
                            {{ item.meta }}
                        </span>
                        <h5 v-if="item.title" class="text-sm sm:text-base font-extrabold text-zinc-950 group-hover:text-blue-600 transition-colors">
                            {{ item.title }}
                        </h5>
                        <p v-if="item.description" class="text-xs leading-relaxed" :class="themeStyle.textMuted">
                            {{ item.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 3. BADGE / PILL BLOCK -->
            <div
                v-else-if="block.type === 'badge'"
                class="flex"
                :class="[
                    block.align === 'center' ? 'justify-center' :
                    block.align === 'right' ? 'justify-end' : 'justify-start'
                ]"
            >
                <span
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold inline-flex items-center gap-1.5 border shadow-2xs"
                    :class="[
                        block.variant === 'emerald' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                        block.variant === 'amber' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                        block.variant === 'zinc' ? 'bg-zinc-100 text-zinc-800 border-zinc-200' :
                        block.variant === 'purple' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                        'bg-blue-50 text-blue-700 border-blue-200'
                    ]"
                >
                    <LunouIcon :name="block.icon || 'sparkles'" className="w-3.5 h-3.5 flex-shrink-0" />
                    <span>{{ block.text }}</span>
                </span>
            </div>

            <!-- 4. ICON STICKER BLOCK -->
            <div
                v-else-if="block.type === 'icon'"
                class="flex flex-col"
                :class="[
                    block.align === 'center' ? 'items-center text-center' :
                    block.align === 'right' ? 'items-end text-right' : 'items-start text-left'
                ]"
            >
                <component
                    :is="block.link ? 'a' : 'div'"
                    :href="block.link || undefined"
                    :target="block.link ? '_blank' : undefined"
                    class="transition-transform duration-200 hover:scale-105 inline-flex items-center justify-center"
                    :class="[
                        block.bg_style === 'circle' ? 'rounded-full shadow-2xs' :
                        block.bg_style === 'rounded' ? 'rounded-2xl shadow-2xs' : '',
                        block.size === 'sm' ? 'w-9 h-9 p-2' :
                        block.size === 'md' ? 'w-12 h-12 p-3' :
                        block.size === 'lg' ? 'w-16 h-16 p-4' : 'w-24 h-24 p-6',
                        block.bg_style !== 'none' && block.color === 'emerald' ? 'bg-emerald-50 text-emerald-600' :
                        block.bg_style !== 'none' && block.color === 'zinc' ? 'bg-zinc-100 text-zinc-900' :
                        block.bg_style !== 'none' && block.color === 'amber' ? 'bg-amber-50 text-amber-600' :
                        block.bg_style !== 'none' && block.color === 'rose' ? 'bg-rose-50 text-rose-600' :
                        block.bg_style !== 'none' && block.color === 'purple' ? 'bg-purple-50 text-purple-600' :
                        block.bg_style !== 'none' ? 'bg-blue-50 text-blue-600' : '',
                        block.bg_style === 'none' && block.color === 'emerald' ? 'text-emerald-600' :
                        block.bg_style === 'none' && block.color === 'zinc' ? 'text-zinc-900' :
                        block.bg_style === 'none' && block.color === 'amber' ? 'text-amber-600' :
                        block.bg_style === 'none' && block.color === 'rose' ? 'text-rose-600' :
                        block.bg_style === 'none' && block.color === 'purple' ? 'text-purple-600' :
                        block.bg_style === 'none' ? 'text-blue-600' : ''
                    ]"
                >
                    <LunouIcon
                        :name="block.name || 'sparkles'"
                        :className="[
                            block.size === 'sm' ? 'w-5 h-5' :
                            block.size === 'md' ? 'w-6 h-6' :
                            block.size === 'lg' ? 'w-8 h-8 sm:w-10 sm:h-10' : 'w-12 h-12 sm:w-14 sm:h-14'
                        ].join(' ')"
                    />
                </component>
                <span v-if="block.label" class="text-xs font-semibold mt-1.5" :class="themeStyle.textMuted">{{ block.label }}</span>
            </div>

            <!-- 5. IMAGE BLOCK -->
            <div
                v-else-if="block.type === 'image'"
                class="space-y-2 flex flex-col"
                :class="[
                    block.align === 'center' ? 'items-center text-center' :
                    block.align === 'right' ? 'items-end text-right' : 'items-start text-left'
                ]"
            >
                <div
                    class="overflow-hidden border border-zinc-200/80 shadow-xs"
                    :class="{
                        'w-full max-h-[500px]': block.width === 'full',
                        'w-full max-w-2xl max-h-[420px]': block.width === 'large',
                        'w-full max-w-md max-h-[340px]': block.width === 'medium',
                        'w-full max-w-xs max-h-64': block.width === 'small',
                        'w-28 h-28 sm:w-36 sm:h-36 rounded-full': block.width === 'avatar',
                        'rounded-2xl': block.width !== 'avatar' && block.rounded === 'rounded-2xl',
                        'rounded-3xl': block.width !== 'avatar' && block.rounded === 'rounded-3xl',
                        'rounded-none': block.width !== 'avatar' && block.rounded === 'rounded-none',
                    }"
                >
                    <img
                        :src="block.url"
                        :alt="block.caption || 'Gambar Portofolio'"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    />
                </div>
                <p v-if="block.caption" class="text-xs italic" :class="themeStyle.textMuted">{{ block.caption }}</p>
            </div>

            <!-- 6. BUTTON BLOCK (WHATSAPP / LINK CTA) -->
            <div
                v-else-if="block.type === 'button'"
                class="flex"
                :class="[
                    block.align === 'center' ? 'justify-center' :
                    block.align === 'right' ? 'justify-end' :
                    block.align === 'full' ? 'w-full' : 'justify-start'
                ]"
            >
                <a
                    :href="block.url || '#'"
                    target="_blank"
                    class="px-5 sm:px-6 py-3 rounded-2xl font-bold text-xs sm:text-sm inline-flex items-center justify-center gap-2.5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 min-h-11"
                    :class="[
                        block.align === 'full' ? 'w-full' : '',
                        block.variant === 'emerald' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' :
                        block.variant === 'black' ? 'bg-zinc-950 hover:bg-black text-white' :
                        block.variant === 'blue' ? 'bg-blue-600 hover:bg-blue-700 text-white' :
                        'border-2 border-zinc-900 text-zinc-900 hover:bg-zinc-100 bg-white'
                    ]"
                >
                    <LunouIcon :name="block.icon || 'whatsapp'" className="w-4 h-4 flex-shrink-0" />
                    <span>{{ block.label }}</span>
                    <span class="text-xs">&rarr;</span>
                </a>
            </div>

            <!-- 7. LINK CARD BLOCK (PLUG & PLAY) -->
            <a
                v-else-if="block.type === 'link_card'"
                :href="block.url || '#'"
                target="_blank"
                :class="['p-4 sm:p-5 rounded-3xl border flex items-center justify-between gap-3 group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 block sm:flex', themeStyle.card]"
            >
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform shadow-2xs">
                        <LunouIcon :name="block.icon || 'behance'" className="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-sm sm:text-base tracking-tight truncate group-hover:text-blue-600 transition-colors">
                                {{ block.title }}
                            </span>
                            <span v-if="block.badge" class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-700 border border-zinc-200 hidden sm:inline-block">
                                {{ block.badge }}
                            </span>
                        </div>
                        <p v-if="block.subtitle" class="text-xs truncate mt-0.5" :class="themeStyle.textMuted">
                            {{ block.subtitle }}
                        </p>
                    </div>
                </div>
                <span class="w-9 h-9 rounded-full bg-zinc-100 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center text-zinc-400 text-sm font-bold transition-all flex-shrink-0 hidden sm:flex">
                    &rarr;
                </span>
            </a>

            <!-- 8. QUOTE BLOCK -->
            <div
                v-else-if="block.type === 'quote'"
                class="p-6 sm:p-8 rounded-3xl bg-zinc-50 border-l-4 border-blue-600 space-y-2 relative"
            >
                <div class="text-blue-600 text-3xl font-serif leading-none">“</div>
                <blockquote class="text-sm sm:text-lg font-medium text-zinc-800 italic leading-relaxed">
                    {{ block.text }}
                </blockquote>
                <div v-if="block.author" class="text-xs sm:text-sm font-bold text-zinc-900 pt-2 flex items-center gap-1.5">
                    <span>— {{ block.author }}</span>
                    <span v-if="block.role" class="text-zinc-500 font-normal">({{ block.role }})</span>
                </div>
            </div>

            <!-- 9. DIVIDER BLOCK -->
            <div v-else-if="block.type === 'divider'" class="py-3">
                <div v-if="block.style === 'line'" class="border-t border-zinc-200/80"></div>
                <div v-else-if="block.style === 'dots'" class="flex justify-center gap-2 text-zinc-300 font-bold">&bull; &bull; &bull;</div>
                <div v-else class="h-6 sm:h-10"></div>
            </div>
        </template>
    </div>
</template>
