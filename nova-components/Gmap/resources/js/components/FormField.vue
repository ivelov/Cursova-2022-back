<template>
    <default-field
        :field="field"
        :errors="errors"
        :show-help-text="showHelpText"
    >
        <template slot="field">
            <input
                type="number"
                step="any"
                class="input form-control form-input form-input-bordered w-full"
                max="90"
                min="-90"
                v-model.number="pos.lat"
                label="Latitude"
            />
            <input
                type="number"
                step="any"
                class="input form-control form-input form-input-bordered w-full mt-2"
                max="180"
                min="-180"
                v-model.number="pos.lng"
                label="Longitude"
            />
            <div class="gmap-size mt-4" ref="map"></div>
        </template>
    </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from "laravel-nova";

export default {
    mixins: [FormField, HandlesValidationErrors],

    props: ["resourceName", "resourceId", "field"],

    data() {
        return {
            pos:{lat: 0, lng: 0},
            map: null,
            marker: null
        };
    },
    watch:{
      pos:{
        handler(newValue, oldValue) {
            if(this.pos.lat == ''){
                this.pos.lat = 0;
            }
            if(this.pos.lng == ''){
                this.pos.lng = 0;
            }
            if(!isNaN(this.pos.lat) && !isNaN(this.pos.lng)){
                this.map.panTo(this.pos);
                this.marker.setPosition(this.pos);
            }
            
            if((this.pos.lat == 0 || isNaN(this.pos.lat)) 
            && (this.pos.lng == 0 || isNaN(this.pos.lng))){
                
                this.marker.setMap(null);
            }else{
                this.marker.setMap(this.map);
            }
        },
        deep:true
      }
    },
    mounted() {
        this.map = new google.maps.Map(this.$refs.map, {
            center: this.pos,
            zoom: 10
        });

        this.marker = new google.maps.Marker({
            position: { lat: -34.397, lng: 150.644 },
            map: this.map,
            draggable: true
        });

        this.marker.addListener("mouseup", this.markerUpdate);
    },
    methods: {
        /*
         * Set the initial, internal value for the field.
         */
        setInitialValue() {
            if(this.field.value){
                let value = JSON.parse(this.field.value);
                if(value.lat == null){
                    value.lat = 0;
                }
                if(value.lng == null){
                    value.lng = 0;
                }
                this.pos = value;
            }else{
                this.pos = {lat: 0, lng: 0};
            }
            
        },

        /**
         * Fill the given FormData object with the field's internal value.
         */
        fill(formData) {
            formData.append("latitude", isNaN(this.pos.lat)? 0 : this.pos.lat);
            formData.append("longitude", isNaN(this.pos.lng)? 0 : this.pos.lng);
        },

        markerUpdate() {
          let pos = this.marker.getPosition();
          this.pos.lat = pos.lat();
          this.pos.lng = pos.lng();
        }
    }
};
</script>