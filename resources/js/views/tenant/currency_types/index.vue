<template>
    <div class="card">
        <div class="card-header">
            <h3 class="my-0">Listado de monedas</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Símbolo</th>
                        <!-- <th>T. Cambio manual</th> -->
                        <th class="text-center">Activo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(row, index) in records" :key="index">
                        <td>{{ index + 1 }}</td>
                        <td>{{ row.id }}</td>
                        <td>{{ row.description }}</td>
                        <td>{{ row.symbol }}</td>
                        <!-- <td>{{ row.manual_exchange }}</td> -->
                        <td class="text-center">{{ row.active }}</td>
                        <td class="text-end">
                            <button type="button" class="btn waves-effect waves-light btn-sm btn-info" @click.prevent="clickCreate(row.id)">Editar</button>
                            <!--<button type="button" class="btn waves-effect waves-light btn-sm btn-danger"  @click.prevent="clickDelete(row.id)">Eliminar</button>-->
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="col">
                    <button type="button" class="btn btn-custom btn-sm  mt-2 mr-2" @click.prevent="clickCreate()"><i class="fa fa-plus-circle"></i> Nuevo</button>
                </div>
            </div>
        </div>
        <currency-types-form :showDialog.sync="showDialog"
                            :recordId="recordId"></currency-types-form>
    </div>
</template>

<script>

    import CurrencyTypesForm from './form.vue'
    import {deletable} from '../../../mixins/deletable'

    export default {
        mixins: [deletable],
        props: ['typeUser'],
        components: {CurrencyTypesForm},
        data() {
            return {
                showDialog: false,
                resource: 'currency_types',
                recordId: null,
                records: [],
            }
        },
        created() {
            this.$eventHub.$on('reloadData', () => {
                this.getData()
            })
            this.getData()
        },
        methods: {
            getData() {
                this.$http.get(`/${this.resource}/records`)
                    .then(response => {
                        this.records = response.data.data
                    })
            },
            clickCreate(recordId = null) {
                this.recordId = recordId
                this.showDialog = true
            },
            clickDelete(id) {
                this.destroy(`/${this.resource}/${id}`).then(() =>
                    this.$eventHub.$emit('reloadData')
                )
            }
        }
    }
</script>
