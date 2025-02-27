<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/dashboard"><i class="fas fa-tachometer-alt"></i></a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>{{ title }}</span>
                </li>
            </ol>
            <div class="right-wrapper pull-right">
                <button
                    type="button"
                    class="btn btn-custom btn-sm mt-2 mr-2"
                    @click.prevent="clickCreate()"
                >
                    <i class="fa fa-plus-circle"></i> Nuevo
                </button>
            </div>
        </div>
        <div class="card mb-0">
            <div class="card-header">
                <h3 class="my-0">{{ title }}</h3>
            </div>
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading">
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Celular</th>
                        <th>Número</th>
                        <th>F. Emisión</th>
                        <th>N° Serie</th>
                        <th>Costo</th>
                        <th class="text-center">Documento</th>
                        <!-- <th>Pago adelantado</th> -->
                        <th></th>
                        <th>Saldo</th>
                        <th class="text-center">Ver</th>
                        <th class="text-end">Acciones</th>
                    </tr>

                    <tr></tr>
                    <tr slot-scope="{ index, row }">
                        <td>{{ index }}</td>
                        <td>
                            {{ row.customer_name }}<br /><small
                                v-text="row.customer_number"
                            ></small>
                        </td>
                        <td class="text-center">{{ row.cellphone }}</td>
                        <td class="text-center">{{ row.id }}</td>
                        <td class="text-center">{{ row.date_of_issue }}</td>
                        <td class="text-center">{{ row.serial_number }}</td>
                        <td class="text-center">{{ row.sum_total }}</td>
                        <td class="text-center">
                            {{ row.number_document_sale_note }}
                        </td>
                        <!-- <td class="text-center">{{ row.prepayment }}</td> -->
                        <td class="text-end">
                            <button
                                type="button"
                                style="min-width: 41px"
                                class="btn waves-effect waves-light btn-sm btn-info m-1__2"
                                @click.prevent="clickPayment(row.id)"
                            >
                                Pagos
                            </button>
                        </td>

                        <td class="text-center">{{ row.balance }}</td>

                        <td class="text-center">
                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-sm btn-info"
                                @click.prevent="clickPrint(row.id)"
                            >
                                PDF
                            </button>
                            <button
                                v-if="row.has_vehicle_format"
                                type="button"
                                class="btn waves-effect waves-light btn-sm btn-success"
                                @click.prevent="clickPrintFormat(row.id)"
                            >
                                Formato vehicular
                            </button>
                        </td>

                        <td class="text-end">
                            <template v-if="!row.has_document_sale_note">
                                <button
                                    type="button"
                                    class="btn waves-effect waves-light btn-sm btn-info"
                                    @click.prevent="clickOptions(row.id)"
                                >
                                    Generar comprobante
                                </button>
                                <button
                                    type="button"
                                    class="btn waves-effect waves-light btn-sm btn-info"
                                    @click.prevent="clickCreate(row.id)"
                                >
                                    Editar
                                </button>
                            </template>

                            <template v-if="typeUser === 'admin'">
                                <button
                                    type="button"
                                    class="btn waves-effect waves-light btn-sm btn-danger"
                                    @click.prevent="clickDelete(row.id)"
                                >
                                    Eliminar
                                </button>
                            </template>
                        </td>
                    </tr>
                </data-table>
            </div>

            <technical-service-options
                :showDialog.sync="showDialogOptions"
                :recordId="recordId"
                :showGenerate="true"
                :showClose="true"
            ></technical-service-options>

            <technical-services-form
                :showDialog.sync="showDialog"
                :recordId="recordId"
            ></technical-services-form>

            <technical-service-payments
                :showDialog.sync="showDialogPayments"
                :recordId="recordId"
                :external="true"
            ></technical-service-payments>
        </div>
    </div>
</template>

<script>
import TechnicalServicesForm from "./form.vue";
import DataTable from "@components/DataTable.vue";
import { deletable } from "@mixins/deletable";
import TechnicalServicePayments from "./partials/payments.vue";
import TechnicalServiceOptions from "./partials/options";
import { mapActions, mapState } from "vuex/dist/vuex.mjs";

export default {
    mixins: [deletable],
    props: ["typeUser", "configuration"],
    computed: {
        ...mapState(["exchange_rate", "config", "currency_types"]),
    },
    components: {
        TechnicalServicesForm,
        DataTable,
        TechnicalServicePayments,
        TechnicalServiceOptions,
    },
    data() {
        return {
            title: null,
            showDialog: false,
            showDialogOptions: false,
            resource: "technical-services",
            recordId: null,
            showDialogPayments: false,
        };
    },
    created() {
        this.loadConfiguration();
        this.$store.commit("setConfiguration", this.configuration);
        this.loadCurrencyTypes();
        this.loadExchangeRate();
        this.title = "Servicios de soporte técnico";
    },
    methods: {
        ...mapActions([
            "loadConfiguration",
            "loadExchangeRate",
            "loadCurrencyTypes",
        ]),
        clickPayment(recordId) {
            this.recordId = recordId;
            this.showDialogPayments = true;
        },
        clickPrintFormat(recordId) {
            window.open(
                `/${this.resource}/format_vehicle/${recordId}`,
                "_blank"
            );
        },
        clickPrint(recordId) {
            window.open(`/${this.resource}/print/${recordId}/a4`, "_blank");
        },
        clickCreate(recordId = null) {
            this.recordId = recordId;
            this.showDialog = true;
        },
        clickDelete(id) {
            this.destroy(`/${this.resource}/${id}`).then(() =>
                this.$eventHub.$emit("reloadData")
            );
        },
        clickOptions(recordId = null) {
            this.recordId = recordId;
            this.showDialogOptions = true;
        },
    },
};
</script>
