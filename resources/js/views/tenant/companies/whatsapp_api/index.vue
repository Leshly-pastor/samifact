<template>
    <div>
        <div class="card">
        <div class="card-header">
            <h3 class="my-0">Whatsapp keys
                
            </h3>
        </div>
        <div class="card-body"> 
            <form autocomplete="off" @submit.prevent="submit">
                <div class="row pt-1">
                    

                    <div class="col-md-12 mt-3">
                        <div class="form-group" :class="{'has-danger': errors.gekawa_1}">
                            <label class="control-label">Llave 1 <span class="text-danger">*</span></label>
                            <el-input v-model="form.gekawa_1"></el-input>
                            <small class="text-danger" v-if="errors.gekawa_1" v-text="errors.gekawa_1[0]"></small>
                        </div>
                    </div>
                    <div class="col-md-12 mt-3">
                        <div class="form-group" :class="{'has-danger': errors.gekawa_2}">
                            <label class="control-label">Llave 2 <span class="text-danger">*</span></label>
                            <el-input v-model="form.gekawa_2"></el-input>
                            <small class="text-danger" v-if="errors.gekawa_2" v-text="errors.gekawa_2[0]"></small>
                        </div>
                    </div>
                </div>
    
                <div class="form-actions text-end pt-2">
                    <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
                </div>
            </form>
        </div> 
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="my-0">ChatBoot de WhatsApp
                
            </h3>
        </div>
        <div class="card-body"> 
            <form autocomplete="off" @submit.prevent="submit">
                <div class="row pt-1">
                    <div class="col-md-12 mt-3">
                        <div class="form-group" :class="{'has-danger': errors.ws_api_phone_number_id}">
                            <label class="control-label">Número de teléfono <span class="text-danger">*</span></label>
                            <el-input v-model="form.ws_api_phone_number_id"></el-input>
                            <small class="text-danger" v-if="errors.ws_api_phone_number_id" v-text="errors.ws_api_phone_number_id[0]"></small>
                        </div>
                    </div>
                </div>
    
                <div class="form-actions text-end pt-2">
                    <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
                </div>
            </form>
        </div> 
    </div>
    </div>
</template>

<style>

.text-color-white{
    color:#FFF !important
}

</style>

<script>

    export default {
        data() {
            return {
                resource: 'companies',
                recordId: null,
                form: {},
                errors: {},
                loading_submit: false,
            }
        },
        created() {
            this.initForm()
            this.getData()
        },
        methods: {
          
            submit(){
                this.loading_submit = true
                this.$http.post(`/${this.resource}/store-whatsapp-api`, this.form)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                        } else {
                            this.$message.error(response.data.message)
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.errors = error.response.data
                        } else {
                            console.log(error)
                        }
                    })
                    .then(() => {
                        this.loading_submit = false
                    })

            },
            initForm(){

                this.form = {
                    ws_api_token : null,
                    ws_api_phone_number_id : null,
                }

                this.errors = {}

            },
            getData() {
                this.$http.get(`/${this.resource}/record-whatsapp-api`)
                    .then(response => {
                        this.form = response.data
                    })
            }, 
        }
    }
</script>
