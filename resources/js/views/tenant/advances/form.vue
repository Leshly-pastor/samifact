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
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="control-label">{{ typeName }}</label>
                            <el-select
                                clearable
                                :remote-method="searchRemotePersons"
                                ref="select_person"
                                v-model="form.person_id"
                                filterable
                                remote
                            >
                                <el-option
                                    v-for="(option, idx) in persons"
                                    :key="idx"
                                    :label="option.description"
                                    :value="option.id"
                                ></el-option>
                            </el-select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.beginning_balance }"
                        >
                            <label class="control-label">Saldo inicial</label>
                            <el-input
                                v-model="form.beginning_balance"
                            ></el-input>
                            <small
                                class="text-danger"
                                v-if="errors.beginning_balance"
                                v-text="errors.beginning_balance[0]"
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.reference_number }"
                        >
                            <label class="control-label"
                                >Número de Referencia</label
                            >
                            <el-input
                                :maxlength="10"
                                v-model="form.reference_number"
                            ></el-input>
                            <small
                                class="text-danger"
                                v-if="errors.reference_number"
                                v-text="errors.reference_number[0]"
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-6" v-if="configuration.multi_companies">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.reference_number }"
                        >
                            <label class="control-label"> Empresas </label>
                            <el-select v-model="form.website_id">
                                <el-option
                                    v-for="(option, idx) in companies"
                                    :key="idx"
                                    :label="option.name"
                                    :value="option.website_id"
                                ></el-option>
                            </el-select>
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
    props: ["type", "showDialog", "recordId", "typeUser", "configuration"],
    data() {
        return {
            typeName: this.type == "customers" ? "Cliente" : "Proveedor",
            loading_submit: false,
            titleDialog: null,
            resource: "advances",
            errors: {},
            form: {},
            user: {},
            all_departments: [],
            all_provinces: [],
            all_districts: [],
            provinces: [],
            districts: [],
            identity_document_types: [],
            users: [],
            companies: [],
            persons: [],
            timer: null,
        };
    },
    async created() {
        // await this.$http.get(`/${this.resource}/tables`).then((response) => {
        //     this.companies = response.data.companies;
        //     this.users = response.data.users;
        //     this.user = response.data.user;
        //     // this.
        // });

        this.initForm();
        this.getPersons();
    },
    computed: {
        disableUser() {
            if (this.typeUser == "admin") {
                return false;
            }
            return true;
        },
    },
    methods: {

        searchRemotePersons(input) {
            if (this.timer) {
                clearTimeout(this.timer);
            }
            this.timer = setTimeout(() => {
                if (input.length > 2) {
                    this.loading_search = true;
                    let parameters = `input=${input}`;

                    this.$http
                        .get(
                            `/${this.resource}/persons/${this.type}?${parameters}`
                        )
                        .then((response) => {
                            console.log("🚀 ~ .then ~ response:", response);
                            this.persons = response.data;
                            this.loading_search = false;
                        });
                }
            }, 600);
        },

        getPersons() {
            this.$http
                .get(`/${this.resource}/persons/${this.type}`)
                .then((response) => {
                    this.persons = response.data;
                });
        },
        beginning() {
            let users_filter = _.find(this.users, { id: this.form.user_id });
            // this.form.reference_number = users_filter.name;
        },
        initForm() {
            this.errors = {};
            this.form = {
                id: null,
                user_id: this.user.id,
                // user: null,
                date_opening: null,
                time_opening: null,
                date_closed: null,
                time_closed: null,
                beginning_balance: 0,
                final_balance: 0,
                income: 0,
                state: true,
                reference_number: null,
                website_id: null,
            };
        },
        create() {
            this.titleDialog = this.recordId
                ? "Editar anticipo de " + this.typeName
                : "Anticipo de " + this.typeName;

            if (this.recordId) {
                this.$http
                    .get(`/${this.resource}/record/${this.recordId}`)
                    .then((response) => {
                        this.form = response.data.data;
                        this.beginning();
                    });
            } else {
                this.form.user_id = this.user.id; //sesion
                this.beginning();
            }
        },
        async openingCashCkeck() {
            let response = await this.$http
                .get(
                    `/${this.resource}/opening_cash_check/${this.form.user_id}`
                )
                .then((response) => {
                    let cash = response.data.cash;
                    return cash ? true : false;
                });
            return response;
        },
        async submit() {
            if (this.configuration.multi_companies) {
                if (!this.form.website_id) {
                    this.$message({
                        message: "Seleccione una empresa",
                        type: "warning",
                        duration: 5000,
                    });
                    return false;
                }
            }
            this.loading_submit = true;

            this.$http
                .post(`/${this.resource}`, this.form)
                .then((response) => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        if (this.form.user_id === this.user.id)
                            this.$eventHub.$emit("openCash");
                        this.$eventHub.$emit("reloadData");
                        // window.open('/pos/init')
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
