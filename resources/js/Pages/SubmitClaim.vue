<template>
    <GuestLayout>
        <Head title="Submit Claim" />

        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Submit A Claim</h2>
            <p class="mt-1 text-sm text-gray-600">Fill in the details below to submit a new healthcare claim</p>
        </div>

        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                {{ $page.props.flash.success }}
            </div>
        </div>

        <div v-if="form.errors.error" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
            {{ form.errors.error }}
        </div>

        <form @submit.prevent="submitClaim" novalidate class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <InputLabel for="insurer_code" value="Insurer Code *" />
                    <div class="relative mt-1">
                        <input
                            id="insurer_code"
                            v-model="form.insurer_code"
                            name="insurer_code"
                            type="text"
                            autocomplete="off"
                            :class="[
                                'block w-full border rounded-md shadow-sm',
                                form.errors.insurer_code 
                                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                                    : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                            ]"
                            placeholder="Enter insurer code (e.g., INS-A)"
                            @input="onInsurerCodeChange"
                            @focus="handleInsurerFocus"
                            @blur="handleInsurerBlur"
                        />
                        <div
                            v-if="showInsurerSuggestions && insurers.length > 0 && filteredInsurers.length > 0"
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                        >
                            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 sticky top-0">
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Insurer Suggestions</p>
                            </div>
                            <button
                                v-for="insurer in filteredInsurers"
                                :key="insurer.id"
                                type="button"
                                @click="selectInsurer(insurer.code)"
                                class="w-full text-left px-4 py-2 hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none border-b border-gray-100 last:border-b-0"
                            >
                                <div class="font-medium text-gray-900">{{ insurer.code }}</div>
                                <div class="text-sm text-gray-500">{{ insurer.name }}</div>
                            </button>
                        </div>
                    </div>
                    <p v-if="selectedInsurerName" class="mt-1 text-xs text-gray-500">
                        {{ selectedInsurerName }}
                    </p>
                    <InputError :message="form.errors.insurer_code" />
                </div>

                <div>
                    <InputLabel for="provider_name" value="Provider Name *" />
                    <TextInput
                        id="provider_name"
                        v-model="form.provider_name"
                        name="provider_name"
                        type="text"
                        :class="[
                            'mt-1 block w-full',
                            form.errors.provider_name 
                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                                : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                        ]"
                        placeholder="Enter provider name"
                    />
                    <InputError :message="form.errors.provider_name" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <InputLabel for="encounter_date" value="Encounter Date *" />
                    <TextInput
                        id="encounter_date"
                        v-model="form.encounter_date"
                        name="encounter_date"
                        type="date"
                        :class="[
                            'mt-1 block w-full',
                            form.errors.encounter_date 
                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                                : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                        ]"
                        :max="maxDate"
                        @change="updateCostEstimate"
                    />
                    <InputError :message="form.errors.encounter_date" />
                </div>

                <div>
                    <InputLabel for="specialty" value="Specialty *" />
                    <div class="relative mt-1">
                        <input
                            id="specialty"
                            v-model="form.specialty"
                            name="specialty"
                            type="text"
                            autocomplete="off"
                            :class="[
                                'block w-full border rounded-md shadow-sm',
                                form.errors.specialty 
                                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                                    : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                            ]"
                            placeholder="Enter specialty (e.g., cardiology, orthopedics)"
                            @input="updateCostEstimate"
                            @focus="showSpecialtySuggestions = true"
                            @blur="handleSpecialtyBlur"
                        />
                        <div
                            v-if="showSpecialtySuggestions && filteredSpecialties.length > 0"
                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                        >
                            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 sticky top-0">
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Specialty Suggestions</p>
                            </div>
                            <button
                                v-for="specialty in filteredSpecialties"
                                :key="specialty"
                                type="button"
                                @click="selectSpecialty(specialty)"
                                class="w-full text-left px-4 py-2 hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none border-b border-gray-100 last:border-b-0 capitalize"
                            >
                                {{ specialty }}
                            </button>
                        </div>
                    </div>
                    <InputError :message="form.errors.specialty" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <InputLabel for="priority_level" value="Priority Level *" />
                    <select
                        id="priority_level"
                        v-model.number="form.priority_level"
                        name="priority_level"
                        :class="[
                            'mt-1 block w-full border rounded-md shadow-sm',
                            form.errors.priority_level 
                                ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                                : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'
                        ]"
                        @change="updateCostEstimate"
                    >
                        <option value="1">1 - Highest Priority (Urgent)</option>
                        <option value="2">2 - High Priority</option>
                        <option value="3">3 - Medium Priority</option>
                        <option value="4">4 - Low Priority</option>
                        <option value="5">5 - Lowest Priority (Routine)</option>
                    </select>
                    <InputError :message="form.errors.priority_level" />
                </div>

                <div v-if="estimatedCost > 0" class="flex items-end">
                    <div class="w-full p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-xs text-blue-600 font-medium mb-1">Estimated Processing Cost</p>
                        <p class="text-2xl font-bold text-blue-900">{{ formatCurrency(estimatedCost) }}</p>
                        <p class="text-xs text-blue-500 mt-1">Based on current claim details</p>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <InputLabel value="Claim Items *" />
                    <span v-if="form.items.length > 0" class="text-sm text-gray-500">
                        {{ form.items.length }} {{ form.items.length === 1 ? 'item' : 'items' }}
                    </span>
                </div>
                
                <!-- Desktop Table View -->
                <div class="hidden md:block rounded-lg overflow-hidden bg-white border border-gray-200 shadow-sm relative">
                    <div
                        ref="tableScrollContainer"
                        class="overflow-y-auto scroll-smooth relative"
                        style="max-height: 320px;"
                        @scroll="handleTableScroll"
                    >
                        <div
                            v-if="showTopScrollIndicator"
                            class="sticky top-12 left-0 right-0 h-10 bg-gradient-to-b from-white via-white/95 to-transparent pointer-events-none z-10 flex items-start justify-center pt-1"
                        >
                            <div class="flex items-center gap-1 text-xs text-gray-600 bg-white px-2 py-1 rounded shadow-sm border border-gray-200">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                </svg>
                                <span>More items above</span>
                            </div>
                        </div>
                        
                        <table class="w-full border-collapse">
                            <thead class="sticky top-0 z-20 bg-gray-50 shadow-sm">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-normal text-gray-900 w-2/5 bg-gray-50 border-b border-gray-200">Item</th>
                                    <th class="px-4 py-3 text-left text-sm font-normal text-gray-900 w-1/5 bg-gray-50 border-b border-gray-200">Unit Price</th>
                                    <th class="px-4 py-3 text-left text-sm font-normal text-gray-900 w-[15%] bg-gray-50 border-b border-gray-200">Qty</th>
                                    <th class="px-4 py-3 text-left text-sm font-normal text-gray-900 w-1/5 bg-gray-50 border-b border-gray-200">Sub Total</th>
                                    <th class="px-4 py-3 w-[5%] bg-gray-50 border-b border-gray-200"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <input
                                        v-model="item.item_name"
                                        type="text"
                                        :name="`items[${index}][item_name]`"
                                        :class="[
                                            'w-full rounded-md px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2',
                                            (form.errors[`items.${index}.item_name`] || form.errors.items) 
                                                ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                                                : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                                        ]"
                                        placeholder="Enter item name"
                                    />
                                    <InputError :message="form.errors[`items.${index}.item_name`]" class="mt-1" />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">$</span>
                                        <input
                                            v-model.number="item.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="999999.99"
                                            :name="`items[${index}][unit_price]`"
                                            :class="[
                                                'w-full rounded-md pl-7 pr-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2',
                                                (form.errors[`items.${index}.unit_price`] || form.errors.items) 
                                                    ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                                                    : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                                            ]"
                                            placeholder="0.00"
                                            @input="handleUnitPriceChange(index, $event)"
                                            @blur="validateAndCalculate(index)"
                                        />
                                    </div>
                                    <InputError :message="form.errors[`items.${index}.unit_price`]" class="mt-1" />
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="1"
                                        max="10000"
                                        :name="`items[${index}][quantity]`"
                                        :class="[
                                            'w-full rounded-md px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2',
                                            (form.errors[`items.${index}.quantity`] || form.errors.items) 
                                                ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                                                : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                                        ]"
                                        placeholder="1"
                                        @input="handleQuantityChange(index, $event)"
                                        @blur="validateAndCalculate(index)"
                                    />
                                    <InputError :message="form.errors[`items.${index}.quantity`]" class="mt-1" />
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="text"
                                        :value="formatCurrency(item.subtotal)"
                                        readonly
                                        class="w-full border border-gray-300 bg-gray-50 rounded-md px-3 py-2 text-sm text-gray-700 cursor-not-allowed"
                                    />
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="removeItem(index)"
                                        class="w-6 h-6 bg-gray-200 border border-gray-300 rounded-md flex items-center justify-center hover:bg-gray-300 focus:outline-none transition-colors"
                                        :disabled="form.items.length === 1"
                                        :class="{ 'opacity-50 cursor-not-allowed': form.items.length === 1 }"
                                        title="Remove item"
                                    >
                                        <span class="text-gray-700 text-sm leading-none">−</span>
                                    </button>
                                </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div
                            v-if="showBottomScrollIndicator"
                            class="sticky bottom-0 left-0 right-0 h-10 bg-gradient-to-t from-white via-white/95 to-transparent pointer-events-none z-10 flex items-end justify-center pb-1"
                        >
                            <div class="flex items-center gap-1 text-xs text-gray-600 bg-white px-2 py-1 rounded shadow-sm border border-gray-200">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                                <span>More items below</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Card View -->
                <div class="md:hidden space-y-4">
                    <div
                        v-for="(item, index) in form.items"
                        :key="index"
                        class="bg-white rounded-lg border border-gray-200 p-4 space-y-3"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-gray-500 uppercase">Item {{ index + 1 }}</span>
                            <button
                                type="button"
                                @click="removeItem(index)"
                                class="w-6 h-6 bg-gray-200 border border-gray-300 rounded-md flex items-center justify-center hover:bg-gray-300 focus:outline-none transition-colors"
                                :disabled="form.items.length === 1"
                                :class="{ 'opacity-50 cursor-not-allowed': form.items.length === 1 }"
                                title="Remove item"
                            >
                                <span class="text-gray-700 text-sm leading-none">−</span>
                            </button>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Item</label>
                            <input
                                v-model="item.item_name"
                                type="text"
                                :name="`items[${index}][item_name]`"
                                :class="[
                                    'w-full rounded-md px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2',
                                    (form.errors[`items.${index}.item_name`] || form.errors.items) 
                                        ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                                        : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                                ]"
                                placeholder="Enter item name"
                            />
                            <InputError :message="form.errors[`items.${index}.item_name`]" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Unit Price</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">$</span>
                                    <input
                                        v-model.number="item.unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="999999.99"
                                        :name="`items[${index}][unit_price]`"
                                        :class="[
                                            'w-full rounded-md pl-7 pr-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2',
                                            (form.errors[`items.${index}.unit_price`] || form.errors.items) 
                                                ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                                                : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                                        ]"
                                        placeholder="0.00"
                                        @input="handleUnitPriceChange(index, $event)"
                                        @blur="validateAndCalculate(index)"
                                    />
                                </div>
                                <InputError :message="form.errors[`items.${index}.unit_price`]" class="mt-1" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Qty</label>
                                <input
                                    v-model.number="item.quantity"
                                    type="number"
                                    min="1"
                                    max="10000"
                                    :name="`items[${index}][quantity]`"
                                    :class="[
                                        'w-full rounded-md px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2',
                                        (form.errors[`items.${index}.quantity`] || form.errors.items) 
                                            ? 'border-red-300 focus:ring-red-500 focus:border-red-500' 
                                            : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'
                                    ]"
                                    placeholder="1"
                                    @input="handleQuantityChange(index, $event)"
                                    @blur="validateAndCalculate(index)"
                                />
                                <InputError :message="form.errors[`items.${index}.quantity`]" class="mt-1" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Sub Total</label>
                            <input
                                type="text"
                                :value="formatCurrency(item.subtotal)"
                                readonly
                                class="w-full border border-gray-300 bg-gray-50 rounded-md px-3 py-2 text-sm text-gray-700 cursor-not-allowed"
                            />
                        </div>
                    </div>
                </div>

                <!-- Desktop Total Section -->
                <div class="hidden md:flex items-center mt-3">
                    <div class="flex items-start w-2/5">
                        <button
                            type="button"
                            @click="addItem"
                            class="w-8 h-8 bg-gray-200 border border-gray-300 rounded-md flex items-center justify-center hover:bg-indigo-50 hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-all group"
                            title="Add Item"
                        >
                            <span class="text-gray-700 text-lg leading-none font-medium group-hover:text-indigo-600">+</span>
                        </button>
                    </div>
                    <div class="w-1/5"></div>
                    <div class="w-[15%]"></div>
                    <div class="w-1/5 flex items-center gap-3">
                        <span class="text-sm text-gray-900">Total</span>
                        <input
                            type="text"
                            :value="formatCurrency(totalAmount)"
                            readonly
                            class="border border-gray-300 bg-gray-50 rounded-md px-3 py-2 text-sm text-gray-900 flex-1 cursor-not-allowed"
                        />
                    </div>
                    <div class="w-[5%]"></div>
                </div>

                <!-- Mobile Total Section -->
                <div class="md:hidden mt-4 flex items-center justify-between bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="addItem"
                            class="w-10 h-10 bg-gray-200 border border-gray-300 rounded-md flex items-center justify-center hover:bg-indigo-50 hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 transition-all group"
                            title="Add Item"
                        >
                            <span class="text-gray-700 text-xl leading-none font-medium group-hover:text-indigo-600">+</span>
                        </button>
                        <span class="text-sm font-medium text-gray-900">Total</span>
                    </div>
                    <input
                        type="text"
                        :value="formatCurrency(totalAmount)"
                        readonly
                        class="border border-gray-300 bg-white rounded-md px-4 py-2 text-base font-semibold text-gray-900 w-32 text-right cursor-not-allowed"
                    />
                </div>
                
                <InputError :message="form.errors.items" class="mt-2" />
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 sm:space-x-4">
                <button
                    type="button"
                    @click="resetForm"
                    class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    :disabled="form.processing"
                >
                    Reset
                </button>
                <PrimaryButton 
                    type="submit"
                    class="w-full sm:w-auto" 
                    :disabled="form.processing"
                >
                    <span v-if="form.processing">Submitting...</span>
                    <span v-else>Submit Claim</span>
                </PrimaryButton>
            </div>
        </form>

        <!-- Floating Dashboard Button (only for logged-in users) -->
        <Link
            v-if="$page.props.auth?.user"
            :href="route('dashboard')"
            class="fixed bottom-6 right-6 w-14 h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 z-50"
            title="Go to Dashboard"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
        </Link>
    </GuestLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useForm, Head, router, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const page = usePage();

const insurers = ref([]);
const estimatedCost = ref(0);
const maxDate = new Date().toISOString().split('T')[0];
let costEstimateTimer = null;
const showInsurerSuggestions = ref(false);
const showSpecialtySuggestions = ref(false);
const selectedInsurerName = ref('');
const tableScrollContainer = ref(null);
const showTopScrollIndicator = ref(false);
const showBottomScrollIndicator = ref(false);
const isTypingInsurer = ref(false);

const specialtyOptions = [
    'cardiology',
    'orthopedics',
    'pediatrics',
    'neurology',
    'dermatology',
    'oncology',
    'general',
    'emergency',
    'surgery',
    'radiology',
];

const form = useForm({
    insurer_code: '',
    provider_name: '',
    encounter_date: '',
    specialty: '',
    priority_level: 5,
    items: [
        {
            item_name: '',
            unit_price: 0,
            quantity: 1,
            subtotal: 0,
        },
    ],
});

const isFormValid = computed(() => {
    if (!form.insurer_code || !form.provider_name || !form.encounter_date || !form.specialty) {
        return false;
    }
    
    const hasValidItems = form.items.every(item => {
        const hasName = item.item_name && item.item_name.trim().length > 0;
        const unitPrice = parseFloat(item.unit_price) || 0;
        const quantity = parseInt(item.quantity) || 0;
        return hasName && unitPrice > 0 && quantity > 0;
    });
    
    return hasValidItems && totalAmount.value > 0;
});

const totalAmount = computed(() => {
    const total = form.items.reduce((sum, item) => {
        const subtotal = parseFloat(item.subtotal) || 0;
        return sum + (isNaN(subtotal) ? 0 : subtotal);
    }, 0);
    return isNaN(total) ? 0 : Math.max(0, total);
});

const filteredInsurers = computed(() => {
    if (!isTypingInsurer.value || !form.insurer_code) {
        return insurers.value;
    }
    const search = form.insurer_code.toLowerCase();
    return insurers.value.filter(insurer =>
        insurer.code.toLowerCase().includes(search) ||
        insurer.name.toLowerCase().includes(search)
    );
});

const filteredSpecialties = computed(() => {
    if (!form.specialty) {
        return specialtyOptions;
    }
    const search = form.specialty.toLowerCase();
    return specialtyOptions.filter(specialty =>
        specialty.toLowerCase().includes(search)
    );
});

const calculateSubtotal = (index) => {
    const item = form.items[index];
    const unitPrice = parseFloat(item.unit_price) || 0;
    const quantity = parseInt(item.quantity) || 0;
    const subtotal = unitPrice * quantity;
    item.subtotal = isNaN(subtotal) ? 0 : Math.max(0, subtotal);
    updateCostEstimate();
};

const addItem = () => {
    form.items.push({
        item_name: '',
        unit_price: 0,
        quantity: 1,
        subtotal: 0,
    });
    updateScrollIndicators();
};

const handleUnitPriceChange = (index, event) => {
    const value = event.target.value;
    const item = form.items[index];
    if (value === '' || value === null || value === undefined) {
        item.unit_price = 0;
    } else {
        const numValue = parseFloat(value);
        item.unit_price = isNaN(numValue) ? 0 : Math.max(0, Math.min(999999.99, numValue));
    }
    calculateSubtotal(index);
};

const handleQuantityChange = (index, event) => {
    const value = event.target.value;
    const item = form.items[index];
    if (value === '' || value === null || value === undefined) {
        item.quantity = 1;
    } else {
        const numValue = parseInt(value);
        item.quantity = isNaN(numValue) ? 1 : Math.max(1, Math.min(10000, numValue));
    }
    calculateSubtotal(index);
};

const validateAndCalculate = (index) => {
    const item = form.items[index];
    if (!item.unit_price || item.unit_price < 0) {
        item.unit_price = 0;
    }
    if (!item.quantity || item.quantity < 1) {
        item.quantity = 1;
    }
    calculateSubtotal(index);
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
        updateCostEstimate();
        updateScrollIndicators();
    }
};

const formatCurrency = (value) => {
    const numValue = parseFloat(value);
    if (isNaN(numValue) || numValue < 0) {
        return '$0.00';
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numValue);
};

const onInsurerCodeChange = () => {
    isTypingInsurer.value = true;
    const insurer = insurers.value.find(i => i.code.toLowerCase() === form.insurer_code.toLowerCase());
    selectedInsurerName.value = insurer ? insurer.name : '';
    updateCostEstimate();
};

const selectInsurer = (code) => {
    form.insurer_code = code;
    const insurer = insurers.value.find(i => i.code === code);
    selectedInsurerName.value = insurer ? insurer.name : '';
    showInsurerSuggestions.value = false;
    isTypingInsurer.value = false;
    updateCostEstimate();
};

const selectSpecialty = (specialty) => {
    form.specialty = specialty;
    showSpecialtySuggestions.value = false;
    updateCostEstimate();
};

const handleInsurerBlur = () => {
    setTimeout(() => {
        showInsurerSuggestions.value = false;
    }, 200);
};

const handleInsurerFocus = () => {
    isTypingInsurer.value = false;
    showInsurerSuggestions.value = true;
};

const handleSpecialtyBlur = () => {
    setTimeout(() => {
        showSpecialtySuggestions.value = false;
    }, 200);
};

const updateCostEstimate = () => {
    if (costEstimateTimer) {
        clearTimeout(costEstimateTimer);
    }

    if (!form.insurer_code || !form.encounter_date || !form.specialty || totalAmount.value <= 0) {
        estimatedCost.value = 0;
        return;
    }

    costEstimateTimer = setTimeout(() => {
        const total = parseFloat(totalAmount.value) || 0;
        if (isNaN(total) || total <= 0) {
            estimatedCost.value = 0;
            return;
        }

        const csrfToken = page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        axios.post(route('claims.estimate-cost'), {
            insurer_code: form.insurer_code,
            encounter_date: form.encounter_date,
            specialty: form.specialty,
            priority_level: form.priority_level,
            total_amount: total,
        }, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            }
        })
        .then(response => {
            const cost = parseFloat(response.data?.estimated_cost) || 0;
            estimatedCost.value = isNaN(cost) ? 0 : Math.max(0, cost);
        })
        .catch(() => {
            estimatedCost.value = 0;
        });
    }, 500);
};

const resetForm = () => {
    form.reset();
    form.items = [
        {
            item_name: '',
            unit_price: 0,
            quantity: 1,
            subtotal: 0,
        },
    ];
    form.insurer_code = '';
    form.specialty = '';
    selectedInsurerName.value = '';
    estimatedCost.value = 0;
    showInsurerSuggestions.value = false;
    showSpecialtySuggestions.value = false;
};

const submitClaim = () => {
    const itemsToSubmit = form.items.map(item => {
        const unitPrice = parseFloat(item.unit_price) || 0;
        const quantity = parseInt(item.quantity) || 0;
        const subtotal = unitPrice * quantity;
        
        return {
            item_name: (item.item_name || '').trim(),
            unit_price: Math.max(0, unitPrice),
            quantity: Math.max(1, quantity),
            subtotal: isNaN(subtotal) ? 0 : Math.max(0, subtotal),
        };
    });

    const totalAmount = itemsToSubmit.reduce((sum, item) => sum + item.subtotal, 0);

    form.transform(() => ({
        ...form.data(),
        items: itemsToSubmit,
        total_amount: totalAmount,
    })).post(route('claims.store'), {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
        },
        onError: () => {},
        onFinish: () => {},
    });
};

watch([() => form.encounter_date, () => form.specialty, () => form.priority_level, totalAmount], () => {
    updateCostEstimate();
});

const handleTableScroll = () => {
    if (!tableScrollContainer.value) return;
    
    const container = tableScrollContainer.value;
    const scrollTop = container.scrollTop;
    const scrollHeight = container.scrollHeight;
    const clientHeight = container.clientHeight;
    
    showTopScrollIndicator.value = scrollTop > 10;
    showBottomScrollIndicator.value = scrollTop + clientHeight < scrollHeight - 10;
};

const updateScrollIndicators = () => {
    if (form.items.length > 5) {
        setTimeout(() => {
            handleTableScroll();
        }, 100);
    } else {
        showTopScrollIndicator.value = false;
        showBottomScrollIndicator.value = false;
    }
};

watch(() => form.items.length, () => {
    updateScrollIndicators();
});

onMounted(async () => {
    try {
        const response = await axios.get(route('insurers.index'));
        insurers.value = response.data || [];
    } catch (error) {
        insurers.value = [];
    }
    
    updateScrollIndicators();
});
</script>
