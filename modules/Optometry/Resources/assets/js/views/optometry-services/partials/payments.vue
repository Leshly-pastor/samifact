<template>
    <el-dialog :title="title"
               :visible="showDialog"
               width="65%"
               @close="close"
               @open="getData">
        <div class="form-body">
            <div class="row">
                <div v-if="records.length > 0"
                     class="col-md-12">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha de pago</th>
                                <th>Método de pago</th>
                                <th>Destino</th>
                                <th>Referencia</th>
                                <!-- <th>Archivo</th> -->
                                <th class="text-end">Monto</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(row, index) in records"
                                :key="index">
                                <template v-if="row.id">
                                    <td>PAGO-{{ row.id }}</td>
                                    <td>{{ row.date_of_payment }}</td>
                                    <td>{{ row.payment_method_type_description }}</td>
                                    <td>{{ row.destination_description }}</td>
                                    <td>{{ row.reference }}</td>
                                    <!-- <td class="text-center">
                                        <button  type="button" v-if="row.filename" class="btn waves-effect waves-light btn-sm btn-primary" @click.prevent="clickDownloadFile(row.filename)">
                                            <i class="fas fa-file-download"></i>
                                        </button>
                                    </td> -->
                                    <td class="text-end">{{ row.payment }}</td>
                                    <td class="series-table-actions text-end">
                                        <button class="btn waves-effect waves-light btn-sm btn-danger"
                                                type="button"
                                                @click.prevent="clickDelete(row.id)">Eliminar
                                        </button>
                                    </td>
                                </template>
                                <template v-else>
                                    <td></td>
                                    <td>
                                        <div :class="{'has-danger': row.errors.date_of_payment}"
                                             class="form-group mb-0">
                                            <el-date-picker v-model="row.date_of_payment"
                                                            :clearable="false"
                                                            format="dd/MM/yyyy"
                                                            type="date"
                                                            value-format="yyyy-MM-dd"></el-date-picker>
                                            <small v-if="row.errors.date_of_payment"
                                                   class="text-danger"
                                                   v-text="row.errors.date_of_payment[0]"></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div :class="{'has-danger': row.errors.payment_method_type_id}"
                                             class="form-group mb-0">
                                            <el-select v-model="row.payment_method_type_id">
                                                <el-option v-for="option in payment_method_types"
                                                           :key="option.id"
                                                           :label="option.description"
                                                           :value="option.id"></el-option>
                                            </el-select>
                                            <small v-if="row.errors.payment_method_type_id"
                                                   class="text-danger"
                                                   v-text="row.errors.payment_method_type_id[0]"></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div :class="{'has-danger': row.errors.payment_destination_id}"
                                             class="form-group mb-0">
                                            <el-select v-model="row.payment_destination_id"
                                                       :disabled="row.payment_destination_disabled"
                                                       filterable>
                                                <el-option v-for="option in payment_destinations"
                                                           :key="option.id"
                                                           :label="option.description"
                                                           :value="option.id"></el-option>
                                            </el-select>
                                            <small v-if="row.errors.payment_destination_id"
                                                   class="text-danger"
                                                   v-text="row.errors.payment_destination_id[0]"></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div :class="{'has-danger': row.errors.reference}"
                                             class="form-group mb-0">
                                            <el-input v-model="row.reference"></el-input>
                                            <small v-if="row.errors.reference"
                                                   class="text-danger"
                                                   v-text="row.errors.reference[0]"></small>
                                        </div>
                                    </td>
                                    <!-- <td>
                                        <div class="form-group mb-0">

                                            <el-upload
                                                    :data="{'index': index}"
                                                    :headers="headers"
                                                    :multiple="false"
                                                    :on-remove="handleRemove"
                                                    :action="`/finances/payment-file/upload`"
                                                    :show-file-list="true"
                                                    :file-list="fileList"
                                                    :on-success="onSuccess"
                                                    :limit="1"
                                                    >
                                                <el-button slot="trigger" type="primary">Seleccione un archivo</el-button>
                                            </el-upload>
                                        </div>
                                    </td> -->
                                    <td>
                                        <div :class="{'has-danger': row.errors.payment}"
                                             class="form-group mb-0">
                                            <el-input v-model="row.payment"></el-input>
                                            <small v-if="row.errors.payment"
                                                   class="text-danger"
                                                   v-text="row.errors.payment[0]"></small>
                                        </div>
                                    </td>
                                    <td class="series-table-actions text-end">
                                        <button class="btn waves-effect waves-light btn-sm btn-info"
                                                type="button"
                                                @click.prevent="clickSubmit(index)">
                                            <i class="fa fa-check"></i>
                                        </button>
                                        <button class="btn waves-effect waves-light btn-sm btn-danger"
                                                type="button"
                                                @click.prevent="clickCancel(index)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </template>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td class="text-end"
                                    colspan="5">TOTAL PAGADO
                                </td>
                                <td class="text-end">{{ document.total_paid }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="text-end"
                                    colspan="5">TOTAL A PAGAR
                                </td>
                                <td class="text-end">{{ document.total }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td class="text-end"
                                    colspan="5">PENDIENTE DE PAGO
                                </td>
                                <td class="text-end">{{ document.total_difference }}</td>
                                <td></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div v-if="showAddButton && (document.total_difference > 0)"
                     class="col-md-12 text-center pt-2">
                    <el-button icon="el-icon-plus"
                               type="primary"
                               @click="clickAddRow">Nuevo
                    </el-button>
                </div>
            </div>
        </div>
    </el-dialog>

</template>

<script>

import {deletable} from '@mixins/deletable'

export default {
    props: ['showDialog', 'recordId'],
    mixins: [deletable],
    data() {
        return {
            title: null,
            resource: 'optometry-service-payments',
            records: [],
            payment_destinations: [],
            payment_method_types: [],
            headers: headers_token,
            index_file: null,
            fileList: [],
            showAddButton: true,
            document: {}
        }
    },
    async created() {
        await this.initForm();
        await this.$http.get(`/${this.resource}/tables`)
            .then(response => {
                this.payment_destinations = response.data.payment_destinations
                this.payment_method_types = response.data.payment_method_types;
                //this.initDocumentTypes()
            })
    },
    methods: {
        clickDownloadFile(filename) {
            window.open(
                `/finances/payment-file/download-file/${filename}/quotations`,
                "_blank"
            );
        },
        onSuccess(response, file, fileList) {
            this.fileList = fileList
            if (response.success) {
                this.index_file = response.data.index
                this.records[this.index_file].filename = response.data.filename
                this.records[this.index_file].temp_path = response.data.temp_path
            } else {
                this.cleanFileList()
                this.$message.error(response.message)
            }
        },
        cleanFileList() {
            this.fileList = []
        },
        handleRemove(file, fileList) {
            this.records[this.index_file].filename = null
            this.records[this.index_file].temp_path = null
            this.fileList = []
            this.index_file = null
        },
        initForm() {
            this.records = [];
            this.fileList = [];
            this.showAddButton = true;
        },
        async getData() {
            this.initForm();
            await this.$http.get(`/${this.resource}/document/${this.recordId}`)
                .then(response => {
                    this.document = response.data;
                    this.title = 'Pagos del servicio técnico: ' + this.document.number_full;
                }).then(() => {
                    this.$http.get(`/${this.resource}/records/${this.recordId}`)
                        .then(response => {
                            this.records = response.data.data
                        })
                })

            this.$eventHub.$emit('reloadDataUnpaid')

        },
        clickAddRow() {
            this.records.push({
                id: null,
                date_of_payment: moment().format('YYYY-MM-DD'),
                payment_method_type_id: null,
                payment_destination_id: null,
                reference: null,
                payment: 0,
                errors: {},
                loading: false
            });
            this.showAddButton = false;
        },
        clickCancel(index) {
            this.records.splice(index, 1);
            this.showAddButton = true;
            this.fileList = []
        },
        clickSubmit(index) {
            if (this.records[index].payment > parseFloat(this.document.total_difference)) {
                this.$message.error('El monto ingresado supera al monto pendiente de pago, verifique.');
                return;
            }

            let form = {
                id: this.records[index].id,
                optometry_service_id: this.recordId,
                date_of_payment: this.records[index].date_of_payment,
                payment_method_type_id: this.records[index].payment_method_type_id,
                payment_destination_id: this.records[index].payment_destination_id,
                reference: this.records[index].reference,
                payment: this.records[index].payment,
            }

            this.$http.post(`/${this.resource}`, form)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.getData();
                        // this.initDocumentTypes()
                        this.$eventHub.$emit('reloadData')
                        this.showAddButton = true;
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch(error => {
                    if (error.response.status === 422) {
                        this.records[index].errors = error.response.data;
                    } else {
                        console.log(error);
                    }
                })
        },
        close() {
            this.$emit('update:showDialog', false);
        },
        clickDelete(id) {
            this.destroy(`/${this.resource}/${id}`).then(() => {
                    this.getData()
                    this.$eventHub.$emit('reloadData')
                }
            )
        }
    }
}
</script>
