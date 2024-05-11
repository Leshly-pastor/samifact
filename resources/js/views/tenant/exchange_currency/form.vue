<template>
    <el-dialog
        :title="titleDialog"
        :visible="showDialog"
        @close="close"
        @open="create"
    >
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-4">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.currency_id }"
                        >
                            <label class="control-label">Moneda</label>
                            <el-select v-model="form.currency_id">
                                <el-option
                                    v-for="item in currencies"
                                    :key="item.id"
                                    :label="item.description"
                                    :value="item.id"
                                ></el-option>
                            </el-select>
                            <small
                                class="text-danger"
                                v-if="errors.currency_id"
                                v-text="errors.currency_id[0]"
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.date }"
                        >
                            <label class="control-label">Fecha</label>
                            <el-date-picker
                                v-model="form.date"
                                type="date"
                                value-format="yyyy-MM-dd"
                                placeholder="Seleccione una fecha"
                            ></el-date-picker>
                            <small
                                class="text-danger"
                                v-if="errors.date"
                                v-text="errors.date[0]"
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.sale }"
                        >
                            <label class="control-label">Venta</label>
                            <el-input
                                v-model="form.sale"
                                type="number"
                                                step="any"
                            ></el-input>
                            <small
                                class="text-danger"
                                v-if="errors.sale"
                                v-text="errors.sale[0]"
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.purchase }"
                        >
                            <label class="control-label">Compra</label>
                            <el-input
                                v-model="form.purchase"
                                type="number"
                                step="any"
                            ></el-input>
                            <small
                                class="text-danger"
                                v-if="errors.purchase"
                                v-text="errors.purchase[0]"
                            ></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions text-end mt-4">
                <el-button @click.prevent="close()">Cancelar</el-button>
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
    props: ["showDialog", "recordId", "currencies"],
    data() {
        return {
            loading_submit: false,
            titleDialog: null,
            resource: "exchange_currency",
            errors: {},
            form: {},
        };
    },
    created() {
        this.initForm();
    },
    methods: {
        initForm() {
            this.errors = {};
            this.form = {
                id: null,
                description: null,
                symbol: null,
                active: true,
            };
        },
        create() {
            this.titleDialog = this.recordId ? "Editar tipo de cambio" : "Nuevo de tipo de cambio";
            if (this.recordId) {
                this.$http
                    .get(`/${this.resource}/record/${this.recordId}`)
                    .then((response) => {
                        this.form = response.data.data;
                    });
            }
        },
        submit() {
            this.loading_submit = true;
            this.$http
                .post(`/${this.resource}`, this.form)
                .then((response) => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.$eventHub.$emit("reloadData");
                        this.close();
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch((error) => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data;
                    } else {
                        console.log(error);
                    }
                })
                .then(() => {
                    this.loading_submit = false;
                });
        },
        close() {
            this.$emit("update:showDialog", false);
            this.initForm();
        },
    },
};
</script>
