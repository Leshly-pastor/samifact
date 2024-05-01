<template>
    <el-dialog
        title="Cobrar multilples documentos"
        :visible="showDialog"
        @close="close"
        @open="open"
        append-to-body
        :close-on-click-modal="false"
        width="75%"
        v-loading="loading"
    >
        <div class="row">
            <div class="col-12 col-md-4 col-lg-4">
                <el-select
                    v-model="form.customer_id"
                    :loading="loading_customer"
                    :remote-method="onFindClients"
                    filterable
                    placeholder="Ingrese uno más caracteres"
                    remote
                    reserve-keyword
                >
                    <el-option
                        v-for="item in clients"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id"
                    >
                    </el-option>
                </el-select>
            </div>
            <div class="col-12 col-md-4 col-lg-4">
                <el-date-picker
                    v-model="form.date_start"
                    placeholder="Inicio de emisión"
                    style="width: 100%"
                    type="date"
                    value-format="yyyy-MM-dd"
                >
                </el-date-picker>
            </div>
            <div class="col-12 col-md-4 col-lg-4">
                <el-date-picker
                    v-model="form.date_end"
                    placeholder="Final Fecha de emisión"
                    style="width: 100%"
                    type="date"
                    value-format="yyyy-MM-dd"
                >
                </el-date-picker>
            </div>
        </div>
        <div class="row d-flex justify-content-end mt-2">
            <div class="col-12 col-md-3 col-lg-3 text-end">
                <el-button
                    type="primary"
                    @click="loadUnpaid"
                    :loading="loading_customer"
                >
                    Buscar
                </el-button>
            </div>
        </div>
        <div class="table-responsive pt-5" v-if="records.length > 0">
            <table class="table table-hover table-stripe">
                <thead>
                    <tr>
                        <th></th>
                        <th>Documento</th>
                        <th>Fecha de emisión</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="dis in records" :key="dis.id">
                        <td>
                            <el-switch
                                v-model="dis.selected"
                                @change="onSelectDocument"
                            ></el-switch>
                        </td>
                        <td>
                            <span>{{ dis.number_full }}</span>
                        </td>
                        <td>{{ dis.date_of_issue | toDate }}</td>
                        <td>{{ dis.total_to_pay }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td></td>
                        <td>Total</td>
                        <td>{{ this.sumTotal.toFixed(2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="table-responsive" v-if="sumTotal">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha de pago</th>
                        <th>
                            Método de pago <span class="text-danger">*</span>
                        </th>
                        <th>Destino <span class="text-danger">*</span></th>
                        <th class="text-center">
                            Monto <span class="text-danger">*</span>
                        </th>
                        <th>¿Pago recibido?</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="form-group mb-0">
                                <el-date-picker
                                    v-model="payment.date_of_payment"
                                    type="date"
                                    :clearable="false"
                                    format="dd/MM/yyyy"
                                    value-format="yyyy-MM-dd"
                                ></el-date-picker>
                            </div>
                        </td>
                        <td>
                            <div class="form-group mb-0">
                                <el-select
                                    v-model="payment.payment_method_type_id"
                                >
                                    <el-option
                                        v-for="option in payment_method_types"
                                        v-show="option.id != '09'"
                                        :key="option.id"
                                        :value="option.id"
                                        :label="option.description"
                                    ></el-option>
                                </el-select>
                            </div>
                        </td>
                        <td>
                            <div class="form-group mb-0">
                                <el-select
                                    v-model="payment.payment_destination_id"
                                    filterable
                                    :disabled="
                                        payment.payment_destination_disabled
                                    "
                                >
                                    <el-option
                                        v-for="option in payment_destinations"
                                        :key="option.id"
                                        :value="option.id"
                                        :label="option.description"
                                    ></el-option>
                                </el-select>
                            </div>
                        </td>

                        <td>
                            <div class="form-group mb-0">
                                <el-input v-model="payment.payment"></el-input>
                            </div>
                        </td>

                        <td class="row no-gutters px-0">
                            <div class="col-md-7">
                                <div class="row no-gutters">
                                    <div class="col-md-3">
                                        <el-radio
                                            class="mb-3 pt-2"
                                            v-model="payment.payment_received"
                                            label="1"
                                            >SI</el-radio
                                        >
                                        <el-radio
                                            v-model="payment.payment_received"
                                            label="0"
                                            >NO</el-radio
                                        >
                                    </div>
                                    <div class="col-md-9">
                                        <el-upload
                                            :data="{ index: 1 }"
                                            :headers="headers"
                                            :multiple="false"
                                            :on-remove="handleRemove"
                                            :action="`/finances/payment-file/upload`"
                                            :show-file-list="true"
                                            :file-list="fileList"
                                            :on-success="onSuccess"
                                            :limit="1"
                                            :disabled="
                                                payment.payment_received == '0'
                                            "
                                            class="pb-1"
                                        >
                                            <template
                                                v-if="
                                                    payment.payment_received ==
                                                    '0'
                                                "
                                            >
                                                <el-button
                                                    type="info"
                                                    class="btn btn-sm"
                                                >
                                                    <i
                                                        class="fas fa-fw fa-upload"
                                                    ></i>
                                                    Cargar voucher
                                                </el-button>
                                            </template>
                                            <template v-else>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-primary"
                                                    slot="trigger"
                                                >
                                                    <i
                                                        class="fas fa-fw fa-upload"
                                                    ></i>
                                                    Cargar voucher
                                                </button>
                                            </template>
                                        </el-upload>
                                        <template
                                            v-if="
                                                payment.payment_received == '1'
                                            "
                                        >
                                            <el-button
                                                type="info"
                                                class="btn btn-sm"
                                            >
                                                <i
                                                    class="fas fa-fw fa-link"
                                                ></i>
                                                Link de pago
                                            </el-button>
                                        </template>
                                        <template v-else>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-primary"
                                                @click="
                                                    showDialogLinkPayment(row)
                                                "
                                            >
                                                <i
                                                    class="fas fa-fw fa-link"
                                                ></i>
                                                Link de pago
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group mb-0">
                                    <el-input
                                        v-model="payment.reference"
                                        placeholder="Referencia y/o N° Operación"
                                        :disabled="
                                            payment.payment_received == '0'
                                        "
                                    ></el-input>
                                </div>
                                <div class="form-group mb-0">
                                    <el-input
                                        v-model="payment.glosa"
                                        placeholder="Glosa"
                                        :disabled="
                                            payment.payment_received == '0'
                                        "
                                    ></el-input>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end" v-if="sumTotal">
            <div class="col-12 col-md-3 col-lg-3 text-end">
                <el-button
                    :disabled="!paymentOk"
                    type="primary"
                    @click="onSavePayment"
                    :loading="payment.loading"
                >
                    Pagar
                </el-button>
            </div>
        </div>
    </el-dialog>
</template>

<script>
import queryString from "query-string";
import moment from "moment";
export default {
    props: ["showDialog", "config"],
    data() {
        return {
            filter: {
                type: "name",
                name: null,
            },
            headers: headers_token,
            payment: null,
            loading_customer:false,
            selected: [],
            records: [],
            resource: "finances/unpaid",
            selectedAll: false,
            selectedIds: [],
            clients: [],
            loading: false,
            pagination: {},
            sumTotal: 0,
            payment_destinations: [],
            payment_method_types: [],
            fileList: [],
            index_file: null,
            form: {
                customer_id: null,
                selecteds: [],
                date_start: null,
                date_end: null,
                period: "between_dates",
                establishment_id: 1,
                stablishmentUnpaidAll: 1,

                // date_of_issue: null,
            },
        };
    },
    async created() {
        this.initPayment();
        this.getTables();

        console.log("🚀 ~ created ~ this.payment:", this.payment);
    },
    computed: {
        paymentOk: function () {
            if (!this.payment) {
                return false;
            }

            return (
                this.payment &&
                this.payment.date_of_payment &&
                this.payment.payment_method_type_id &&
                this.payment.payment_destination_id &&
                this.payment.payment > 0
            );
        },
        cash_payment_metod: function () {
            return _.filter(this.payment_method_types, { is_credit: 0 });
        },
    },
    methods: {
        initPayment() {
            this.payment = {
                id: null,
                date_of_payment: moment().format("YYYY-MM-DD"),
                payment_method_type_id: null,
                payment_destination_id: null,
                reference: null,
                filename: null,
                temp_path: null,
                glosa: null,
                payment: parseFloat(this.sumTotal),
                // payment: 0,
                errors: {},
                loading: false,
                payment_received: "1",
            };
        },
        async onSavePayment() {
            let body = { ...this.payment, ...this.form };
            try {
                this.loading = true;
                const response = await this.$http.post(
                    "/finances/unpaid/multiple-pay",
                    body
                );
                let { success, message } = response.data;
                if (success) {
                    this.initForm();
                    this.initPayment();
                    this.records = [];
                    this.sumTotal = 0;
                    this.$eventHub.$emit("reloadDataUnpaid");
                    
                    // this.
                    this.$message.success(message);
                } else {
                    this.$message.error(
                        "Ocurrió un error al realizar los pagos"
                    );
                }
            } catch (e) {
                this.$message.error("Ocurrió un error al realizar los pagos");
            }finally{
                this.loading = false;
            }
        },
        onSuccess(response, file, fileList) {
            this.fileList = fileList;

            if (response.success) {
                this.index_file = response.data.index;
                this.payment.filename = response.data.filename;
                this.payment.temp_path = response.data.temp_path;
            } else {
                this.fileList = [];
                this.$message.error(response.message);
            }
        },
        handleRemove(file, fileList) {
            this.payment.filename = null;
            this.payment.temp_path = null;
            this.fileList = [];
            this.index_file = null;
        },

        async getTables() {
            const response = await this.$http.get(`/document_payments/tables`);
            this.payment_destinations = response.data.payment_destinations;
            console.log(
                "🚀 ~ getTables ~ payment_destinations:",
                this.payment_destinations
            );
            this.payment_method_types = response.data.payment_method_types;
        },
        getPaymentDestinationId() {
            if (
                this.config.destination_sale &&
                this.payment_destinations.length > 0
            ) {
                let cash = _.find(this.payment_destinations, { id: "cash" });

                return cash ? cash.id : this.payment_destinations[0].id;
            }

            return null;
        },
        onSelectDocument() {
            this.form.selecteds = [];
            this.sumTotal = 0;
            let total = 0;
            this.records.map((d) => {
                if (d.selected) {
                    total += Number(d.total_to_pay);
                    this.form.selecteds.push({
                        id: d.id,
                        type: d.type,
                        payment: d.total_to_pay,
                    });
                }
            });
            this.sumTotal += total;
            this.payment.payment = parseFloat(this.sumTotal).toFixed(2);
        },
        onFindClients(query) {
            this.filter.name = query;
            this.onFetchClients();
        },
        open() {
            this.initForm();
            this.onFetchClients();
        },
        initForm() {
            this.form = {
                establishment_id: null,
                period: "between_dates",
                date_start: moment().format("YYYY-MM-DD"),
                date_end: moment().format("YYYY-MM-DD"),
                customer_id: null,
                user_id: null,
                // payment_method_type_id: null,
                customer_id: null,
                establishment_id: 1,
                stablishmentUnpaidAll: 1,
            };
        },
        getQueryParameters() {
            return queryString.stringify({
                page: this.pagination.current_page,
                limit: this.limit,
                ...this.form,
            });
        },
        async loadUnpaid() {
            if (!this.form.customer_id) {
                this.$message({
                    type: "warning",
                    message: "Seleccione un cliente",
                });
                return;
            }
            this.loading = true;

            await this.$http
                .get(`/${this.resource}/records?${this.getQueryParameters()}`)
                .then((response) => {
                    this.records = response.data.data;
                    this.pagination = response.data.meta;
                    this.pagination.per_page = parseInt(
                        response.data.meta.per_page
                    );
                    const setting = response.data.configuration;
                    this.records.sort(function (a, b) {
                        return (
                            new Date(a.date_of_issue) -
                            new Date(b.date_of_issue)
                        );
                    });
                    this.records = this.records.map((r) => {
                        if (setting.apply_arrears) {
                            r.arrears = parseFloat(
                                r.delay_payment * setting.arrears_amount
                            ).toFixed(2);
                        } else {
                            r.arrears = 0;
                        }
                        r.selected = false;
                        return r;
                    });
                })
                .catch((error) => {})
                .then(() => {
                    this.loading = false;
                });
        },
        onFetchClients() {
            this.loading = true;
            this.records = [];
            this.form.selecteds = [];
            const params = this.filter;
            this.$http
                .get("/customers/list", { params })
                .then((response) => {
                    this.clients = response.data.data;
                })
                .finally(() => (this.loading = false));
        },
        close() {
            this.$emit("update:showDialog", false);
        },
    },
};
</script>
