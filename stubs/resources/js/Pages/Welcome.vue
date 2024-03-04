<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CContainer, CCol, CRow, CCard, CCardBody, CButton } from '@coreui/vue-pro';
import { CIcon } from '@coreui/icons-vue';
import { cibPhp, cibLaravel, cibVueJs, cibCoreui } from '@coreui/icons';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Welcome" />

    <CContainer>
        <CRow>
            <CCol class="text-center mt-6 mb-6">
                <h2>
                    Welcome to the JayDee's scaffolding !!
                </h2>
                <i class="mt-3">This was configured <b>without</b> users support.</i>
            </CCol>
        </CRow>
        <CRow>
            <CCol>
                <CCard class="text-center">
                    <CCardBody>
                        <CCardTitle><CIcon :icon="cibPhp" class="text-php" size="4xl"/></CCardTitle>
                        <CButton color="primary" href="https://php.net">PHP {{ phpVersion }}</CButton>
                    </CCardBody>
                </CCard>
            </CCol>
            <CCol>
                <CCard class="text-center">
                    <CCardBody>
                        <CCardTitle><CIcon :icon="cibLaravel" class="text-laravel" size="4xl"/></CCardTitle>
                        <CButton color="primary" href="https://laravel.com">Laravel {{ laravelVersion }}</CButton>
                    </CCardBody>
                </CCard>
            </CCol>
            <CCol>
                <CCard class="text-center">
                    <CCardBody>
                        <CCardTitle><CIcon :icon="cibVueJs" class="text-vuejs" size="4xl"/></CCardTitle>
                        <CButton color="primary" href="https://vuejs.org">Vue.js</CButton>
                    </CCardBody>
                </CCard>
            </CCol>
            <CCol>
                <CCard class="text-center">
                    <CCardBody>
                        <CCardTitle><CIcon :icon="cibCoreui" class="text-coreui" size="4xl"/></CCardTitle>
                        <CButton color="primary" href="https://coreui.io">CoreUI</CButton>
                    </CCardBody>
                </CCard>
            </CCol>
        </CRow>
    </CContainer>
</template>
