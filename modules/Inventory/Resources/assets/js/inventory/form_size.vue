<template>
    <el-dialog :title="titleDialog" width="30%"  :visible="showDialog"  @open="create"  :close-on-click-modal="false" :close-on-press-escape="false" append-to-body :show-close="false">

        <div class="form-body">
            <div class="row" >
                <div class="col-lg-12 col-md-12 table-responsive">
                   
                    <table width="100%" class="table">
                        <thead>
                            <tr width="100%">
                                <th >Talla</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in sizes_" :key="index" width="100%">
                                <!-- <td>{{index}}</td> -->
                            
                                <td>
                                    {{row.size}}
                                </td>
                                <td>
                                    <el-input-number v-model="row.quantity" :min="0"  :step="1" size="small" ></el-input-number>
                                </td>
                               
                            </tr>
                        </tbody>
                    </table>


                </div>

            </div>
        </div>

        <div class="form-actions text-end pt-2">
            <el-button @click.prevent="close()">Cerrar</el-button>
            <!-- <el-button type="primary" @click="submit" >Guardar</el-button> -->
        </div>
    </el-dialog>
</template>

<script>
    export default {
        props: ['showDialog', 'sizes', 'recordId'],
        data() {
            return {
                titleDialog: 'Tallas',
                loading: false,
                errors: {},
                form: {},
                search: '',
                sizes_: []
            }
        },
        async created() {

        },
        watch:{
            sizes(val)
            {
                this.sizes_ = val
            }
        },
        methods: {
            filter()
            {

                if(this.search)
                {
                    this.sizes_ = this.sizes.filter( x => x.series.toUpperCase().includes(this.search.toUpperCase()))
                }
                else{
                    this.sizes_ = this.sizes
                }
            },
            create(){

            },
            async submit(){

                // let val_sizes = await this.validateLots()
                // if(!val_sizes.success)
                //     return this.$message.error(val_sizes.message);

                // await this.$emit('addRowLot', this.sizes);
                // await this.$emit('update:showDialog', false)

            },
            close() {
                this.$emit('update:showDialog', false)
                this.$emit('addRowSize', this.sizes_);
            },
            async clickCancelSubmit() {

                // this.$emit('addRowLot', []);
                // await this.$emit('update:showDialog', false)

            },
        }
    }
</script>
