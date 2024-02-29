<template>
    <el-dialog
        :title="titleDialog"
        :visible="showDialog"
        @open="create"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :show-close="false"
    >
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <span class="h4">Remitente</span>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div
                            class="form-group"
                            :class="{
                                'has-danger':
                                    errors.s_document_id,
                            }"
                        >
                            <label class="control-label"
                                >Tipo Doc. Identidad</label
                            >
                            <el-select
                                v-model="
                                    dispatch.s_document_id
                                "
                                filterable
                                popper-class="el-select-identity_document_type"
                            >
                                <el-option
                                    v-for="option in identity_document_types"
                                    :key="option.id"
                                    :value="option.id"
                                    :label="option.description"
                                ></el-option>
                            </el-select>
                            <small
                                class="text-danger"
                                v-if="errors.s_document_id"
                                v-text="
                                    errors.s_document_id[0]
                                "
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div
                            class="form-group"
                            :class="{
                                'has-danger':
                                    errors.sender_number_identity_document,
                            }"
                        >
                            <label class="control-label"
                                >Número documento</label
                            >
                            <el-input
                                v-model="
                                    dispatch.sender_number_identity_document
                                "
                                :maxlength="maxLength"
                            >
                            </el-input>
                            <small
                                class="text-danger"
                                v-if="errors.sender_number_identity_document"
                                v-text="
                                    errors.sender_number_identity_document[0]
                                "
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div
                            class="form-group"
                            :class="{
                                'has-danger': errors.sender_passenger_fullname,
                            }"
                        >
                            <label class="control-label"
                                >Nombres y Apellidos</label
                            >
                            <el-input
                                v-model="dispatch.sender_passenger_fullname"
                            ></el-input>
                            <small
                                class="text-danger"
                                v-if="errors.sender_passenger_fullname"
                                v-text="errors.sender_passenger_fullname[0]"
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.sender_telephone }"
                        >
                            <label class="control-label">Celular</label>
                            <el-input
                                v-model="dispatch.sender_telephone"
                            ></el-input>
                            <small
                                class="text-danger"
                                v-if="errors.sender_telephone"
                                v-text="errors.sender_telephone[0]"
                            ></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-body">
                <div class="row">
                    <span class="h4">Destinatario</span>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div
                            class="form-group"
                            :class="{
                                'has-danger':
                                    errors.r_document_id,
                            }"
                        >
                            <label class="control-label"
                                >Tipo Doc. Identidad</label
                            >
                            <el-select
                                v-model="
                                    dispatch.r_document_id
                                "
                                filterable
                                popper-class="el-select-identity_document_type"
                            >
                                <el-option
                                    v-for="option in identity_document_types"
                                    :key="option.id"
                                    :value="option.id"
                                    :label="option.description"
                                ></el-option>
                            </el-select>
                            <small
                                class="text-danger"
                                v-if="
                                    errors.r_document_id
                                "
                                v-text="
                                    errors
                                        .r_document_id[0]
                                "
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div
                            class="form-group"
                            :class="{
                                'has-danger':
                                    errors.recipient_number_identity_document,
                            }"
                        >
                            <label class="control-label"
                                >Número documento</label
                            >
                            <el-input
                                v-model="
                                    dispatch.recipient_number_identity_document
                                "
                                :maxlength="maxLength"
                            >
                            </el-input>
                            <small
                                class="text-danger"
                                v-if="errors.recipient_number_identity_document"
                                v-text="
                                    errors.recipient_number_identity_document[0]
                                "
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div
                            class="form-group"
                            :class="{
                                'has-danger':
                                    errors.recipient_passenger_fullname,
                            }"
                        >
                            <label class="control-label"
                                >Nombres y Apellidos</label
                            >
                            <el-input
                                v-model="dispatch.recipient_passenger_fullname"
                            ></el-input>
                            <small
                                class="text-danger"
                                v-if="errors.recipient_passenger_fullname"
                                v-text="errors.recipient_passenger_fullname[0]"
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div
                            class="form-group"
                            :class="{
                                'has-danger': errors.recipient_telephone,
                            }"
                        >
                            <label class="control-label">Celular</label>
                            <el-input
                                v-model="dispatch.recipient_telephone"
                            ></el-input>
                            <small
                                class="text-danger"
                                v-if="errors.recipient_telephone"
                                v-text="errors.recipient_telephone[0]"
                            ></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions text-end mt-4">
                <el-button @click.prevent="close(true)">Cancelar</el-button>
                <el-button
                    type="primary"
                    native-type="submit"
                    :loading="loading_submit"
                    >Guardar</el-button
                >
            </div>
        </form>
    </el-dialog>
</template>

<script>
export default {
    props: ["showDialog", "dispatch"],
    data() {
        return {
            transport: {},
            titleDialog: "Datos para la encomienda",
            loading_submit: false,
            errors: {},
            form: {},
            resource: "bussiness_turns",
            company: {},
            configuration: {},
            identity_document_types: [],
            locations: [],
        };
    },
    computed: {
        maxLength: function () {
            if (this.dispatch.identity_document_type_id === "1") {
                return 8;
            } else {
                return 12;
            }
        },
    },
    async created() {
        await this.$http
            .get(`/${this.resource}/tables/transports`)
            .then((response) => {
                this.identity_document_types =
                    response.data.identity_document_types;
                this.locations = response.data.locations;
            });
    },
    methods: {
        create() {},
        validateData() {
            //valida todos las propiedades del objeto dispatch, cada una de ellas y si alguna es llena el objeto errors con la descripcion
            let errors = {};
            if (!this.dispatch.s_document_id || this.dispatch.s_document_id === "")
                errors.s_document_id = [
                    "El tipo de documento de identidad es obligatorio",
                ];
            if (!this.dispatch.sender_number_identity_document || this.dispatch.sender_number_identity_document === "")
                errors.sender_number_identity_document = [
                    "El número de documento de identidad es obligatorio",
                ];
            if (!this.dispatch.sender_passenger_fullname || this.dispatch.sender_passenger_fullname === "")
                errors.sender_passenger_fullname = [
                    "El nombre del remitente es obligatorio",
                ];
            if (!this.dispatch.sender_telephone || this.dispatch.sender_telephone === "")
                errors.sender_telephone = [
                    "El celular del remitente es obligatorio",
                ];
            if (!this.dispatch.r_document_id || this.dispatch.r_document_id === "")
                errors.r_document_id = [
                    "El tipo de documento de identidad es obligatorio",
                ];
            if (!this.dispatch.recipient_number_identity_document || this.dispatch.recipient_number_identity_document === "")
                errors.recipient_number_identity_document = [
                    "El número de documento de identidad es obligatorio",
                ];
            if (!this.dispatch.recipient_passenger_fullname || this.dispatch.recipient_passenger_fullname === "")
                errors.recipient_passenger_fullname = [
                    "El nombre del destinatario es obligatorio",
                ];
            if (!this.dispatch.recipient_telephone || this.dispatch.recipient_telephone === "")
                errors.recipient_telephone = [
                    "El celular del destinatario es obligatorio",
                ];
            this.errors = errors;
            return Object.keys(errors).length === 0;
        },
        submit() {
            if (!this.validateData()) return;
            console.log("🚀 ~ file: dispatch_form.vue:311 ~ validateData ~ this.errors:", this.errors)
            
            this.$emit("addDocumentTransport", this.transport);
            this.close(false);
        },
        close(flag) {
            if (flag) this.$emit("addDocumentTransport", {});
            this.errors = {};
            this.$emit("update:showDialog", false);
        },
    },
};
</script>
